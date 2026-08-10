<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\FacturaLinea;
use App\Modules\Ventas\Models\Pedido;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Emitir una factura: el momento en que una venta se vuelve un ingreso y una
 * cuenta por cobrar.
 *
 * Emitir es un PUNTO DE NO RETORNO. A partir de ahi la factura no se edita: se
 * corrige con una nota de credito, y las dos quedan. Es lo que hace que el
 * ingreso reportado se pueda auditar.
 *
 * DEPENDENCIA DE FINANZAS: la poliza de ingreso + impuesto + CxC la generaria
 * `ServicioContabilizarPoliza`, que aun no existe. Mientras tanto, el
 * Contabilizador de contingencia deja constancia en la bitacora y la factura se
 * emite igual (docs/david.md §33). Cuando Finanzas publique el suyo se cambia
 * el binding y este servicio no se toca.
 */
class ServicioFactura
{
    public function __construct(
        private readonly Contabilizador $contabilizador,
        private readonly ServicioPedido $pedidos,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la factura ya no es editable
     */
    public function agregarLinea(Factura $factura, array $datos): FacturaLinea
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} esta {$factura->estado->label()}: ".
                'una factura emitida se corrige con una nota de credito, no editandola.'
            );
        }

        return DB::transaction(function () use ($factura, $datos): FacturaLinea {
            $producto = isset($datos['producto_id']) ? Producto::find($datos['producto_id']) : null;
            $impuestoId = $datos['impuesto_id'] ?? $producto?->impuesto_id;
            $tasa = $this->tasaDelImpuesto($impuestoId);

            $totales = CalculadoraLinea::calcular(
                (float) $datos['cantidad'],
                (float) $datos['precio_unitario'],
                (float) ($datos['porcentaje_descuento'] ?? 0),
                (float) ($datos['monto_descuento'] ?? 0),
                $tasa,
            );

            $linea = $factura->lineas()->create([
                'pedido_linea_id' => $datos['pedido_linea_id'] ?? null,
                'producto_id' => $datos['producto_id'] ?? null,
                'descripcion' => $datos['descripcion'] ?? $producto?->nombre,
                'cantidad' => $datos['cantidad'],
                'precio_unitario' => $datos['precio_unitario'],
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'monto_descuento' => $totales['monto_descuento'],
                'impuesto_id' => $impuestoId,
                'tasa_impuesto' => $tasa,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $factura->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si la factura ya no es editable */
    public function eliminarLinea(Factura $factura, FacturaLinea $linea): void
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException("La factura {$factura->numero_factura} ya no admite cambios.");
        }

        DB::transaction(function () use ($factura, $linea): void {
            $linea->delete();
            $factura->recalcularTotales();
        });
    }

    /**
     * Crea la factura en borrador con lo SURTIDO de un pedido.
     *
     * Se factura lo que salio del almacen, no lo que se pidio: cobrar lo que
     * todavia no se entrego es la forma mas rapida de perder a un cliente.
     *
     * @throws RuntimeException si el pedido no admite factura o no hay nada surtido
     */
    public function crearDesdePedido(Pedido $pedido): Factura
    {
        if (! $pedido->estado->admiteFactura()) {
            throw new RuntimeException(
                "El pedido {$pedido->numero_pedido} esta {$pedido->estado->label()}: ".
                'solo se factura lo que ya se surtio.'
            );
        }

        $pedido->load('lineas.producto', 'cliente.condicionPago');

        return DB::transaction(function () use ($pedido): Factura {
            $factura = Factura::create([
                'organizacion_id' => $pedido->organizacion_id,
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => $this->vencimientoSegunCondicion($pedido->cliente?->condicionPago),
                'condicion_pago_id' => $pedido->cliente?->condicion_pago_id,
                'moneda' => $pedido->moneda,
                'notas' => "Generada desde el pedido {$pedido->numero_pedido}.",
            ]);

            $lineas = 0;

            foreach ($pedido->lineas as $linea) {
                $surtida = (float) $linea->cantidad_surtida;

                if ($surtida <= 0) {
                    continue;
                }

                $this->agregarLinea($factura, [
                    'pedido_linea_id' => $linea->id,
                    'producto_id' => $linea->producto_id,
                    'descripcion' => $linea->descripcion,
                    'cantidad' => $surtida,
                    'precio_unitario' => (float) $linea->precio_unitario,
                    'porcentaje_descuento' => (float) $linea->porcentaje_descuento,
                    'monto_descuento' => 0,
                    'impuesto_id' => $linea->impuesto_id,
                ]);

                $lineas++;
            }

            if ($lineas === 0) {
                throw new RuntimeException(
                    "El pedido {$pedido->numero_pedido} no tiene nada surtido que facturar."
                );
            }

            return $factura->refresh();
        });
    }

    /**
     * Emite la factura: la contabiliza y la vuelve inmutable.
     *
     * @param  int  $versionFila  la version que el usuario tenia en pantalla
     *
     * @throws RuntimeException
     */
    public function emitir(Factura $factura, int $versionFila): Factura
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} esta {$factura->estado->label()} y ya fue emitida."
            );
        }

        if ($factura->lineas()->count() === 0) {
            throw new RuntimeException('Una factura sin lineas no se puede emitir.');
        }

        if ((float) $factura->total <= 0) {
            throw new RuntimeException('Una factura en cero no se emite.');
        }

        return DB::transaction(function () use ($factura, $versionFila): Factura {
            $afectadas = Factura::query()
                ->whereKey($factura->getKey())
                ->where('version_fila', $versionFila)
                ->update([
                    'estado' => EstadoFactura::Emitida->value,
                    'version_fila' => $versionFila + 1,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                throw new RuntimeException(
                    'Alguien mas modifico esta factura mientras la revisabas. Vuelve a cargarla antes de emitirla.'
                );
            }

            $factura->refresh();

            $this->contabilizador->contabilizar('factura', $factura, [
                'subtotal' => (float) $factura->subtotal,
                'impuesto' => (float) $factura->total_impuesto,
                'total' => (float) $factura->total,
                'cliente_id' => $factura->cliente_id,
                'periodo_fiscal_id' => $factura->periodo_fiscal_id,
            ]);

            $factura->registrarBitacora('emitida', [], [
                'estado' => EstadoFactura::Emitida->value,
                'total' => (float) $factura->total,
            ]);

            if ($factura->pedido !== null) {
                $this->pedidos->actualizarEstadoPorFacturas($factura->pedido);
            }

            return $factura;
        });
    }

    /**
     * Cancela la factura y pide la reversa contable.
     *
     * Con cobros o notas de credito no se cancela: primero hay que cancelar
     * esos, porque quedarian apuntando a una factura que ya no existe.
     *
     * @throws RuntimeException
     */
    public function cancelar(Factura $factura): Factura
    {
        if (! $factura->estado->esCancelable()) {
            throw new RuntimeException("La factura {$factura->numero_factura} ya esta cancelada.");
        }

        if ($factura->cobros()->where('estado', 'aplicado')->exists()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} tiene cobros aplicados. Cancelalos primero."
            );
        }

        if ($factura->notasCredito()->where('estado', 'emitida')->exists()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} tiene notas de credito emitidas. Cancelalas primero."
            );
        }

        return DB::transaction(function () use ($factura): Factura {
            $eraEmitida = $factura->estado !== EstadoFactura::Borrador;

            $factura->estado = EstadoFactura::Cancelada;
            $factura->save();

            if ($eraEmitida) {
                $this->contabilizador->reversar('factura', $factura, ['total' => (float) $factura->total]);
            }

            $factura->registrarBitacora('cancelada', [], ['estado' => EstadoFactura::Cancelada->value]);

            if ($factura->pedido !== null) {
                $this->pedidos->actualizarEstadoPorFacturas($factura->pedido);
            }

            return $factura->refresh();
        });
    }

    /**
     * La fecha de vencimiento a partir de la condicion de pago del cliente.
     *
     * Los dias viajan en `catalogos.valor` (NET30 -> "30"), que es justo para lo
     * que se sembraron asi y evita una tabla extra.
     */
    private function vencimientoSegunCondicion(?Catalogo $condicion): ?string
    {
        if ($condicion === null || ! is_numeric($condicion->valor)) {
            return null;
        }

        return Carbon::today()->addDays((int) $condicion->valor)->toDateString();
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
