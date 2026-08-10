<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Models\User;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Enums\EstadoRequisicion;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\Requisicion;
use App\Modules\Compras\Models\RequisicionLinea;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El ciclo de vida de una requisicion: enviar, aprobar o rechazar, convertir.
 *
 * Una requisicion no compromete dinero con nadie, asi que su ciclo es corto y
 * su unica decision real es la aprobacion. Lo interesante pasa al CONVERTIRLA:
 * ahi nace la orden de compra, con proveedor y precios, que si compromete.
 */
class ServicioRequisicion
{
    public function enviar(Requisicion $requisicion): Requisicion
    {
        if (! $requisicion->estado->esEditable()) {
            throw new RuntimeException(
                "La requisicion {$requisicion->numero_requisicion} esta {$requisicion->estado->label()} y ya se envio."
            );
        }

        if ($requisicion->lineas()->count() === 0) {
            throw new RuntimeException('Una requisicion sin lineas no se puede enviar a revision.');
        }

        $requisicion->estado = EstadoRequisicion::Enviada;
        $requisicion->save();
        $requisicion->registrarBitacora('enviada', [], ['estado' => EstadoRequisicion::Enviada->value]);

        return $requisicion->refresh();
    }

    /**
     * Aprueba o rechaza. Es una sola operacion porque es una sola decision, y
     * asi la ruta y el privilegio (`compras.requisiciones.aprobar`) tambien son
     * uno solo.
     */
    public function revisar(Requisicion $requisicion, User $usuario, bool $aprobada, ?string $comentario = null): Requisicion
    {
        if (! $requisicion->estado->esRevisable()) {
            throw new RuntimeException(
                "Solo se revisa una requisicion enviada; {$requisicion->numero_requisicion} esta {$requisicion->estado->label()}."
            );
        }

        $nuevo = $aprobada ? EstadoRequisicion::Aprobada : EstadoRequisicion::Rechazada;

        $requisicion->estado = $nuevo;

        if (filled($comentario)) {
            $requisicion->notas = trim((string) $requisicion->notas."\n[".$nuevo->label().'] '.$comentario);
        }

        $requisicion->save();

        $requisicion->registrarBitacora($aprobada ? 'aprobada' : 'rechazada', [], [
            'estado' => $nuevo->value,
            'reviso' => $usuario->name,
            'comentario' => $comentario,
        ]);

        return $requisicion->refresh();
    }

    /**
     * Convierte la requisicion aprobada en una orden de compra a un proveedor.
     *
     * Copia las lineas tomando el COSTO ACTUAL del catalogo como punto de
     * partida; el comprador lo ajusta despues con lo que realmente cotizo el
     * proveedor. Se copia y no se referencia porque a partir de aqui la orden
     * tiene vida propia: cambiar el catalogo no debe reescribirla.
     *
     * @throws RuntimeException si la requisicion no esta aprobada
     */
    public function convertirEnOrden(Requisicion $requisicion, Proveedor $proveedor): OrdenCompra
    {
        if (! $requisicion->estado->esConvertible()) {
            throw new RuntimeException(
                "Solo se convierte una requisicion aprobada; {$requisicion->numero_requisicion} esta {$requisicion->estado->label()}."
            );
        }

        return DB::transaction(function () use ($requisicion, $proveedor): OrdenCompra {
            $orden = OrdenCompra::create([
                'organizacion_id' => $requisicion->organizacion_id,
                'proveedor_id' => $proveedor->id,
                'requisicion_id' => $requisicion->id,
                'fecha' => now()->toDateString(),
                'fecha_entrega' => $requisicion->fecha_requerida?->toDateString(),
                'moneda' => $proveedor->moneda,
                'notas' => "Generada desde la requisicion {$requisicion->numero_requisicion}.",
            ]);

            $lineas = app(ServicioOrdenCompra::class);

            foreach ($requisicion->lineas as $linea) {
                $producto = $linea->producto;

                $lineas->agregarLinea($orden, [
                    'producto_id' => $linea->producto_id,
                    'descripcion' => $producto?->nombre,
                    'cantidad' => (float) $linea->cantidad_solicitada,
                    'costo_unitario' => (float) ($producto?->costo ?? 0),
                    'impuesto_id' => $producto?->impuesto_id,
                ]);
            }

            $requisicion->estado = EstadoRequisicion::Convertida;
            $requisicion->save();
            $requisicion->registrarBitacora('convertida', [], [
                'estado' => EstadoRequisicion::Convertida->value,
                'orden_compra' => $orden->numero_orden,
            ]);

            return $orden->refresh();
        });
    }

    /**
     * Da la requisicion por atendida.
     *
     * Se cierra sola cuando su orden se recibe completa (ver ServicioRecepcion),
     * pero tambien se puede cerrar a mano: a veces lo que se pidio se resolvio
     * de otra forma.
     */
    public function cerrar(Requisicion $requisicion): Requisicion
    {
        if (in_array($requisicion->estado, [EstadoRequisicion::Cerrada, EstadoRequisicion::Rechazada], true)) {
            throw new RuntimeException(
                "La requisicion {$requisicion->numero_requisicion} ya esta {$requisicion->estado->label()}."
            );
        }

        $requisicion->estado = EstadoRequisicion::Cerrada;
        $requisicion->save();
        $requisicion->registrarBitacora('cerrada', [], ['estado' => EstadoRequisicion::Cerrada->value]);

        return $requisicion->refresh();
    }

    /**
     * Cierra la requisicion cuando su orden ya llego completa.
     *
     * Lo llama ServicioRecepcion. No lanza excepcion si no aplica: cerrar es un
     * efecto secundario de recibir, y no debe hacer fallar la recepcion.
     */
    public function cerrarSiSuOrdenEstaCompleta(OrdenCompra $orden): void
    {
        $requisicion = $orden->requisicion;

        if ($requisicion === null || $orden->estado !== EstadoOrdenCompra::Recibida) {
            return;
        }

        if ($requisicion->estado === EstadoRequisicion::Convertida) {
            $requisicion->estado = EstadoRequisicion::Cerrada;
            $requisicion->save();
            $requisicion->registrarBitacora('cerrada', [], [
                'estado' => EstadoRequisicion::Cerrada->value,
                'motivo' => "La orden {$orden->numero_orden} se recibio completa.",
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la requisicion ya no es editable
     */
    public function agregarLinea(Requisicion $requisicion, array $datos): RequisicionLinea
    {
        if (! $requisicion->estado->esEditable()) {
            throw new RuntimeException('Solo se le agregan lineas a una requisicion en borrador.');
        }

        // Se valida aqui y no solo en el FormRequest porque convertirEnOrden()
        // depende de que el producto exista para copiar su costo.
        Producto::findOrFail($datos['producto_id']);

        return $requisicion->lineas()->create($datos);
    }
}
