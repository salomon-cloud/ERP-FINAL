<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Compras\Enums\EstadoDevolucionCompra;
use App\Modules\Compras\Models\DevolucionCompra;
use App\Modules\Compras\Models\DevolucionCompraLinea;
use App\Modules\Compras\Models\FacturaProveedorLinea;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Devolver mercancia al proveedor.
 *
 * Aplicarla saca la mercancia del almacen (`devolucion_salida`) y pide el cargo
 * a favor en Finanzas. Cuelga de la FACTURA y no de la recepcion porque lo que
 * se reclama es dinero ya facturado.
 *
 * NO SE DEVUELVE MAS DE LO FACTURADO: la comprobacion es por linea de factura,
 * sumando lo que ya se devolvio en otras devoluciones aplicadas.
 */
class ServicioDevolucionCompra
{
    public function __construct(
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly Contabilizador $contabilizador,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si no es editable o se devuelve de mas
     */
    public function agregarLinea(DevolucionCompra $devolucion, array $datos): DevolucionCompraLinea
    {
        if (! $devolucion->estado->esEditable()) {
            throw new RuntimeException(
                "La devolucion {$devolucion->numero_devolucion} esta {$devolucion->estado->label()} y ya no admite cambios."
            );
        }

        $facturaLinea = FacturaProveedorLinea::with('producto')->findOrFail($datos['factura_proveedor_linea_id']);

        if ((int) $facturaLinea->factura_proveedor_id !== (int) $devolucion->factura_proveedor_id) {
            throw new RuntimeException('Esa linea pertenece a otra factura de proveedor.');
        }

        $this->verificarNoDevolverDeMas($devolucion, $facturaLinea, (float) $datos['cantidad']);

        return DB::transaction(function () use ($devolucion, $facturaLinea, $datos): DevolucionCompraLinea {
            $totales = CalculadoraLinea::calcular(
                (float) $datos['cantidad'],
                (float) $facturaLinea->costo_unitario,
                0,
                0,
                (float) $facturaLinea->tasa_impuesto,
            );

            $linea = $devolucion->lineas()->create([
                'factura_proveedor_linea_id' => $facturaLinea->id,
                'producto_id' => $facturaLinea->producto_id,
                'cantidad' => $datos['cantidad'],
                // El costo es el de la factura, no el del catalogo: se reclama
                // lo que se pago, no lo que vale hoy.
                'costo_unitario' => $facturaLinea->costo_unitario,
                'tasa_impuesto' => $facturaLinea->tasa_impuesto,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $devolucion->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si la devolucion ya no es editable */
    public function eliminarLinea(DevolucionCompra $devolucion, DevolucionCompraLinea $linea): void
    {
        if (! $devolucion->estado->esEditable()) {
            throw new RuntimeException("La devolucion {$devolucion->numero_devolucion} ya no admite cambios.");
        }

        DB::transaction(function () use ($devolucion, $linea): void {
            $linea->delete();
            $devolucion->recalcularTotales();
        });
    }

    /**
     * Aplica la devolucion: la mercancia sale del almacen.
     *
     * @throws RuntimeException si falta el almacen, no hay lineas o no hay existencia
     */
    public function aplicar(DevolucionCompra $devolucion): DevolucionCompra
    {
        if (! $devolucion->estado->esEditable()) {
            throw new RuntimeException(
                "La devolucion {$devolucion->numero_devolucion} esta {$devolucion->estado->label()} y ya se aplico."
            );
        }

        $devolucion->load('lineas.producto');

        if ($devolucion->lineas->isEmpty()) {
            throw new RuntimeException('Una devolucion sin lineas no se puede aplicar.');
        }

        $almacen = $devolucion->almacenDeSalida();

        if ($almacen === null) {
            throw new RuntimeException(
                'No se puede saber de que almacen sale la mercancia: la factura no tiene una recepcion aplicada.'
            );
        }

        return DB::transaction(function () use ($devolucion, $almacen): DevolucionCompra {
            foreach ($devolucion->lineas as $linea) {
                if ($linea->producto_id === null) {
                    continue;
                }

                $this->movimientos->registrar(
                    TipoMovimiento::DevolucionSalida,
                    (int) $linea->producto_id,
                    (int) $almacen->id,
                    (float) $linea->cantidad,
                    (float) $linea->costo_unitario,
                    $devolucion,
                    ['organizacion_id' => $devolucion->organizacion_id],
                );
            }

            $devolucion->estado = EstadoDevolucionCompra::Aplicada;
            $devolucion->save();

            $this->contabilizador->contabilizar('devolucion_compra', $devolucion, [
                'subtotal' => (float) $devolucion->subtotal,
                'impuesto' => (float) $devolucion->total_impuesto,
                'total' => (float) $devolucion->total,
                'proveedor_id' => $devolucion->proveedor_id,
            ]);

            $devolucion->registrarBitacora('aplicada', [], [
                'estado' => EstadoDevolucionCompra::Aplicada->value,
                'almacen' => $almacen->codigo,
            ]);

            return $devolucion->refresh();
        });
    }

    /**
     * Cancela la devolucion. Si ya estaba aplicada, la mercancia vuelve al
     * almacen con el movimiento contrario.
     *
     * @throws RuntimeException
     */
    public function cancelar(DevolucionCompra $devolucion): DevolucionCompra
    {
        if (! $devolucion->estado->esCancelable()) {
            throw new RuntimeException("La devolucion {$devolucion->numero_devolucion} ya esta cancelada.");
        }

        return DB::transaction(function () use ($devolucion): DevolucionCompra {
            $estabaAplicada = $devolucion->estado === EstadoDevolucionCompra::Aplicada;

            if ($estabaAplicada) {
                $this->movimientos->revertirDocumento($devolucion);
                $this->contabilizador->reversar('devolucion_compra', $devolucion, [
                    'total' => (float) $devolucion->total,
                ]);
            }

            $devolucion->estado = EstadoDevolucionCompra::Cancelada;
            $devolucion->save();
            $devolucion->registrarBitacora('cancelada', [], [
                'estado' => EstadoDevolucionCompra::Cancelada->value,
                'movimientos_revertidos' => $estabaAplicada,
            ]);

            return $devolucion->refresh();
        });
    }

    /** @throws RuntimeException */
    private function verificarNoDevolverDeMas(
        DevolucionCompra $devolucion,
        FacturaProveedorLinea $facturaLinea,
        float $cantidad,
    ): void {
        $facturada = (float) $facturaLinea->cantidad;

        $yaDevuelta = (float) DevolucionCompraLinea::query()
            ->where('factura_proveedor_linea_id', $facturaLinea->id)
            ->whereHas('devolucion', fn ($consulta) => $consulta->whereIn('estado', ['borrador', 'aplicada']))
            ->sum('cantidad');

        if ($yaDevuelta + $cantidad > $facturada + 0.000001) {
            throw new RuntimeException(sprintf(
                'No se puede devolver mas de lo facturado: de %s se facturaron %s y ya hay %s en devoluciones.',
                $facturaLinea->producto?->nombre ?? 'ese producto',
                rtrim(rtrim(number_format($facturada, 6, '.', ''), '0'), '.'),
                rtrim(rtrim(number_format($yaDevuelta, 6, '.', ''), '0'), '.'),
            ));
        }
    }
}
