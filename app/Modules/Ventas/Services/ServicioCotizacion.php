<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Enums\EstadoCotizacion;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\CotizacionLinea;
use App\Modules\Ventas\Models\Pedido;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El ciclo de vida de una cotizacion: capturar, enviar, aceptar o rechazar,
 * convertir en pedido.
 *
 * Convertir COPIA las lineas al pedido en vez de referenciarlas. A partir de
 * ese momento el pedido tiene vida propia: si la cotizacion se corrigiera
 * despues, el pedido -- que ya es un compromiso -- no debe cambiar solo.
 */
class ServicioCotizacion
{
    public function __construct(private readonly ServicioResolverPrecio $precios) {}

    /**
     * Agrega una linea. El precio se resuelve solo si no viene capturado.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la cotizacion ya no es editable
     */
    public function agregarLinea(Cotizacion $cotizacion, array $datos): CotizacionLinea
    {
        if (! $cotizacion->estado->esEditable()) {
            throw new RuntimeException(
                "La cotizacion {$cotizacion->numero_cotizacion} esta {$cotizacion->estado->label()} y ya no admite cambios."
            );
        }

        return DB::transaction(function () use ($cotizacion, $datos): CotizacionLinea {
            $producto = Producto::findOrFail($datos['producto_id']);
            $cantidad = (float) $datos['cantidad'];

            $precio = filled($datos['precio_unitario'] ?? null)
                ? (float) $datos['precio_unitario']
                : $this->precios->resolver($producto, $cotizacion->cliente, $cantidad, $cotizacion->listaPrecio);

            $impuestoId = $datos['impuesto_id'] ?? $producto->impuesto_id;
            $tasa = $this->tasaDelImpuesto($impuestoId);

            $totales = CalculadoraLinea::calcular(
                $cantidad,
                $precio,
                (float) ($datos['porcentaje_descuento'] ?? 0),
                (float) ($datos['monto_descuento'] ?? 0),
                $tasa,
            );

            $linea = $cotizacion->lineas()->create([
                'producto_id' => $producto->id,
                'descripcion' => $datos['descripcion'] ?? $producto->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'monto_descuento' => $totales['monto_descuento'],
                'impuesto_id' => $impuestoId,
                'tasa_impuesto' => $tasa,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $cotizacion->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si la cotizacion ya no es editable */
    public function eliminarLinea(Cotizacion $cotizacion, CotizacionLinea $linea): void
    {
        if (! $cotizacion->estado->esEditable()) {
            throw new RuntimeException("La cotizacion {$cotizacion->numero_cotizacion} ya no admite cambios.");
        }

        DB::transaction(function () use ($cotizacion, $linea): void {
            $linea->delete();
            $cotizacion->recalcularTotales();
        });
    }

    /** @throws RuntimeException */
    public function enviar(Cotizacion $cotizacion): Cotizacion
    {
        if (! $cotizacion->estado->esEditable()) {
            throw new RuntimeException(
                "La cotizacion {$cotizacion->numero_cotizacion} esta {$cotizacion->estado->label()} y ya se envio."
            );
        }

        if ($cotizacion->lineas()->count() === 0) {
            throw new RuntimeException('Una cotizacion sin lineas no se puede enviar.');
        }

        $cotizacion->estado = EstadoCotizacion::Enviada;
        $cotizacion->save();
        $cotizacion->registrarBitacora('enviada', [], ['estado' => EstadoCotizacion::Enviada->value]);

        return $cotizacion->refresh();
    }

    /**
     * La respuesta del cliente. Aceptar y rechazar son la misma decision con
     * signo contrario, por eso un solo metodo y una sola ruta.
     *
     * @throws RuntimeException
     */
    public function responder(Cotizacion $cotizacion, bool $aceptada): Cotizacion
    {
        if (! $cotizacion->estado->admiteRespuesta()) {
            throw new RuntimeException(
                "Solo responde el cliente a una cotizacion enviada; {$cotizacion->numero_cotizacion} esta {$cotizacion->estado->label()}."
            );
        }

        if ($aceptada && $cotizacion->esta_vencida) {
            throw new RuntimeException(
                "La cotizacion {$cotizacion->numero_cotizacion} vencio el ".
                $cotizacion->vigencia->format('d/m/Y').'. Genera una nueva con precios vigentes.'
            );
        }

        $nuevo = $aceptada ? EstadoCotizacion::Aceptada : EstadoCotizacion::Rechazada;

        $cotizacion->estado = $nuevo;
        $cotizacion->save();
        $cotizacion->registrarBitacora($aceptada ? 'aceptada' : 'rechazada', [], ['estado' => $nuevo->value]);

        return $cotizacion->refresh();
    }

    /**
     * Convierte la cotizacion aceptada en un pedido en borrador.
     *
     * El pedido nace en BORRADOR y no confirmado a proposito: falta elegir de
     * que almacen sale cada renglon, y confirmar es lo que aparta existencia.
     *
     * @param  int|null  $almacenId  almacen por omision para todas las lineas
     *
     * @throws RuntimeException si la cotizacion no esta aceptada
     */
    public function convertirEnPedido(Cotizacion $cotizacion, ?int $almacenId = null): Pedido
    {
        if (! $cotizacion->estado->esConvertible()) {
            throw new RuntimeException(
                "Solo se convierte una cotizacion aceptada; {$cotizacion->numero_cotizacion} esta {$cotizacion->estado->label()}."
            );
        }

        return DB::transaction(function () use ($cotizacion, $almacenId): Pedido {
            $pedido = Pedido::create([
                'organizacion_id' => $cotizacion->organizacion_id,
                'cotizacion_id' => $cotizacion->id,
                'cliente_id' => $cotizacion->cliente_id,
                'lista_precio_id' => $cotizacion->lista_precio_id,
                'fecha' => now()->toDateString(),
                'moneda' => $cotizacion->moneda,
                'notas' => "Generado desde la cotizacion {$cotizacion->numero_cotizacion}.",
            ]);

            // Las lineas se copian CON sus totales ya calculados, no se
            // recalculan: el precio que el cliente acepto es el que vale,
            // aunque la lista haya cambiado entre la oferta y la conversion.
            foreach ($cotizacion->lineas as $linea) {
                $pedido->lineas()->create([
                    'producto_id' => $linea->producto_id,
                    'almacen_id' => $almacenId,
                    'descripcion' => $linea->descripcion,
                    'cantidad' => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'porcentaje_descuento' => $linea->porcentaje_descuento,
                    'monto_descuento' => $linea->monto_descuento,
                    'impuesto_id' => $linea->impuesto_id,
                    'tasa_impuesto' => $linea->tasa_impuesto,
                    'monto_impuesto' => $linea->monto_impuesto,
                    'subtotal' => $linea->subtotal,
                    'total' => $linea->total,
                ]);
            }

            $pedido->recalcularTotales();

            $cotizacion->estado = EstadoCotizacion::Convertida;
            $cotizacion->save();
            $cotizacion->registrarBitacora('convertida', [], [
                'estado' => EstadoCotizacion::Convertida->value,
                'pedido' => $pedido->numero_pedido,
            ]);

            return $pedido->refresh();
        });
    }

    /** @throws RuntimeException */
    public function cancelar(Cotizacion $cotizacion): Cotizacion
    {
        if (! $cotizacion->estado->esCancelable()) {
            throw new RuntimeException(
                "La cotizacion {$cotizacion->numero_cotizacion} esta {$cotizacion->estado->label()} y ya no se puede cancelar."
            );
        }

        $cotizacion->estado = EstadoCotizacion::Cancelada;
        $cotizacion->save();
        $cotizacion->registrarBitacora('cancelada', [], ['estado' => EstadoCotizacion::Cancelada->value]);

        return $cotizacion->refresh();
    }

    /** La tasa del impuesto como fraccion (0.16 para el 16%). */
    private function tasaDelImpuesto(?int $impuestoId): float
    {
        if ($impuestoId === null) {
            return 0.0;
        }

        return (float) (DB::table('impuestos')->where('id', $impuestoId)->value('tasa') ?? 0);
    }
}
