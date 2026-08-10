<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\OrdenCompraLinea;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El ciclo de vida de una orden de compra: capturar, confirmar, enviar,
 * recalcular lo recibido y cancelar.
 *
 * `confirmar` es la autorizacion interna y usa BLOQUEO OPTIMISTA: si alguien
 * mas movio la orden mientras el autorizador la revisaba, la actualizacion
 * condicionada no afecta ninguna fila y se aborta. Autorizar una orden con
 * cantidades viejas es comprometerse a comprar otra cosa.
 */
class ServicioOrdenCompra
{
    /**
     * Agrega un renglon con sus totales ya calculados.
     *
     * El costo y la tasa se congelan aqui: son una foto del acuerdo, no una
     * referencia al catalogo.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la orden ya no es editable
     */
    public function agregarLinea(OrdenCompra $orden, array $datos): OrdenCompraLinea
    {
        if (! $orden->estado->esEditable()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} esta {$orden->estado->label()} y ya no admite cambios en sus lineas."
            );
        }

        return DB::transaction(function () use ($orden, $datos): OrdenCompraLinea {
            $tasa = $this->tasaDelImpuesto($datos['impuesto_id'] ?? null);

            $totales = CalculadoraLinea::calcular(
                (float) $datos['cantidad'],
                (float) $datos['costo_unitario'],
                (float) ($datos['porcentaje_descuento'] ?? 0),
                (float) ($datos['monto_descuento'] ?? 0),
                $tasa,
            );

            $linea = $orden->lineas()->create([
                'producto_id' => $datos['producto_id'] ?? null,
                'almacen_id' => $datos['almacen_id'] ?? null,
                'descripcion' => $datos['descripcion'] ?? null,
                'cantidad' => $datos['cantidad'],
                'costo_unitario' => $datos['costo_unitario'],
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'monto_descuento' => $totales['monto_descuento'],
                'impuesto_id' => $datos['impuesto_id'] ?? null,
                'tasa_impuesto' => $tasa,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $orden->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si la orden ya no es editable */
    public function eliminarLinea(OrdenCompra $orden, OrdenCompraLinea $linea): void
    {
        if (! $orden->estado->esEditable()) {
            throw new RuntimeException("La orden {$orden->numero_orden} ya no admite cambios en sus lineas.");
        }

        DB::transaction(function () use ($orden, $linea): void {
            $linea->delete();
            $orden->recalcularTotales();
        });
    }

    /**
     * Autorizacion interna. A partir de aqui la orden ya puede recibir.
     *
     * @param  int  $versionFila  la version que el usuario tenia en pantalla
     *
     * @throws RuntimeException si no es un borrador, no tiene lineas o cambio la version
     */
    public function confirmar(OrdenCompra $orden, int $versionFila): OrdenCompra
    {
        if (! $orden->estado->esEditable()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} esta {$orden->estado->label()} y ya fue confirmada."
            );
        }

        if ($orden->lineas()->count() === 0) {
            throw new RuntimeException('Una orden de compra sin lineas no se puede confirmar.');
        }

        return DB::transaction(function () use ($orden, $versionFila): OrdenCompra {
            $afectadas = OrdenCompra::query()
                ->whereKey($orden->getKey())
                ->where('version_fila', $versionFila)
                ->update([
                    'estado' => EstadoOrdenCompra::Confirmada->value,
                    'version_fila' => $versionFila + 1,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                throw new RuntimeException(
                    'Alguien mas modifico esta orden mientras la revisabas. Vuelve a cargarla antes de confirmarla.'
                );
            }

            $orden->refresh();
            $orden->registrarBitacora('confirmada', [], ['estado' => EstadoOrdenCompra::Confirmada->value]);

            return $orden;
        });
    }

    /**
     * Marca que el proveedor ya tiene la orden.
     *
     * Es un cambio de estado sin efectos: sirve para que el comprador sepa que
     * ya no hace falta volver a mandarla.
     */
    public function enviar(OrdenCompra $orden): OrdenCompra
    {
        if (! $orden->estado->esEditable()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} esta {$orden->estado->label()} y ya salio de borrador."
            );
        }

        if ($orden->lineas()->count() === 0) {
            throw new RuntimeException('Una orden de compra sin lineas no se puede enviar.');
        }

        $orden->estado = EstadoOrdenCompra::Enviada;
        $orden->save();
        $orden->registrarBitacora('enviada', [], ['estado' => EstadoOrdenCompra::Enviada->value]);

        return $orden->refresh();
    }

    /**
     * Recalcula el estado a partir de lo que ya llego.
     *
     * Lo llama ServicioRecepcion despues de aplicar o de cancelar una
     * recepcion. Es una funcion de lo recibido, no una decision de nadie.
     */
    public function actualizarEstadoPorRecepciones(OrdenCompra $orden): OrdenCompra
    {
        $orden->load('lineas');

        $nuevo = match (true) {
            $orden->estaCompletamenteRecibida() => EstadoOrdenCompra::Recibida,
            $orden->tieneAlgoRecibido() => EstadoOrdenCompra::RecibidaParcial,
            default => EstadoOrdenCompra::Confirmada,
        };

        if ($orden->estado !== $nuevo) {
            $anterior = $orden->estado;
            $orden->estado = $nuevo;
            $orden->save();
            $orden->registrarBitacora('recepcion_actualizada',
                ['estado' => $anterior->value], ['estado' => $nuevo->value]);
        }

        return $orden->refresh();
    }

    /** Marca la orden como facturada. Lo llama ServicioFacturaProveedor. */
    public function marcarFacturada(OrdenCompra $orden): void
    {
        if ($orden->estado === EstadoOrdenCompra::Recibida) {
            $orden->estado = EstadoOrdenCompra::Facturada;
            $orden->save();
            $orden->registrarBitacora('facturada', [], ['estado' => EstadoOrdenCompra::Facturada->value]);
        }
    }

    /**
     * Cancela la orden, siempre que no haya entrado nada al almacen.
     *
     * @throws RuntimeException
     */
    public function cancelar(OrdenCompra $orden): OrdenCompra
    {
        if (! $orden->estado->esCancelable()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} esta {$orden->estado->label()} y ya no se puede cancelar."
            );
        }

        $orden->load('lineas');

        if ($orden->tieneAlgoRecibido()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} ya tiene mercancia recibida. ".
                'Cancela primero sus recepciones o levanta una devolucion a proveedor.'
            );
        }

        $orden->estado = EstadoOrdenCompra::Cancelada;
        $orden->save();
        $orden->registrarBitacora('cancelada', [], ['estado' => EstadoOrdenCompra::Cancelada->value]);

        return $orden->refresh();
    }

    /**
     * La tasa de un impuesto de Finanzas, como fraccion (0.16 para el 16%).
     *
     * Se lee con el Query Builder porque `impuestos` es de Finanzas y ese
     * modulo todavia no publica su modelo. Cuando lo haga, se cambia aqui.
     */
    private function tasaDelImpuesto(?int $impuestoId): float
    {
        if ($impuestoId === null) {
            return 0.0;
        }

        return (float) (DB::table('impuestos')->where('id', $impuestoId)->value('tasa') ?? 0);
    }
}
