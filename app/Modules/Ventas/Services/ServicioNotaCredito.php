<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use App\Modules\Ventas\Enums\EstadoNotaCredito;
use App\Modules\Ventas\Models\FacturaLinea;
use App\Modules\Ventas\Models\NotaCredito;
use App\Modules\Ventas\Models\NotaCreditoLinea;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Notas de credito: como se corrige una factura ya emitida.
 *
 * DOS REGLAS QUE SOSTIENEN TODO:
 *
 *  1. No se acredita mas de lo facturado. Se comprueba renglon por renglon
 *     contra `factura_lineas`, contando lo que ya acreditaron otras notas.
 *  2. Solo el motivo `devolucion` regresa mercancia al almacen. Un descuento
 *     posterior o la correccion de un error acreditan dinero sin que vuelva ni
 *     una caja; meterlas al inventario inventaria existencia que nadie tiene.
 */
class ServicioNotaCredito
{
    public function __construct(
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly Contabilizador $contabilizador,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si no es editable o se acredita de mas
     */
    public function agregarLinea(NotaCredito $nota, array $datos): NotaCreditoLinea
    {
        if (! $nota->estado->esEditable()) {
            throw new RuntimeException(
                "La nota {$nota->numero_nota} esta {$nota->estado->label()} y ya no admite cambios."
            );
        }

        $facturaLinea = FacturaLinea::with('producto')->findOrFail($datos['factura_linea_id']);

        if ((int) $facturaLinea->factura_id !== (int) $nota->factura_id) {
            throw new RuntimeException('Ese renglon pertenece a otra factura.');
        }

        $this->verificarNoAcreditarDeMas($facturaLinea, (float) $datos['cantidad']);

        return DB::transaction(function () use ($nota, $facturaLinea, $datos): NotaCreditoLinea {
            $totales = CalculadoraLinea::calcular(
                (float) $datos['cantidad'],
                (float) $facturaLinea->precio_unitario,
                0,
                0,
                (float) $facturaLinea->tasa_impuesto,
            );

            $linea = $nota->lineas()->create([
                'factura_linea_id' => $facturaLinea->id,
                'producto_id' => $facturaLinea->producto_id,
                'descripcion' => $facturaLinea->descripcion,
                'cantidad' => $datos['cantidad'],
                // El precio es el de la FACTURA, no el del catalogo: se acredita
                // lo que se cobro, no lo que costaria hoy.
                'precio_unitario' => $facturaLinea->precio_unitario,
                'impuesto_id' => $facturaLinea->impuesto_id,
                'tasa_impuesto' => $facturaLinea->tasa_impuesto,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $nota->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si la nota ya no es editable */
    public function eliminarLinea(NotaCredito $nota, NotaCreditoLinea $linea): void
    {
        if (! $nota->estado->esEditable()) {
            throw new RuntimeException("La nota {$nota->numero_nota} ya no admite cambios.");
        }

        DB::transaction(function () use ($nota, $linea): void {
            $linea->delete();
            $nota->recalcularTotales();
        });
    }

    /**
     * Emite la nota: acredita al cliente y, si es devolucion, regresa la
     * mercancia al almacen.
     *
     * @param  int|null  $almacenId  a que almacen vuelve la mercancia
     *
     * @throws RuntimeException
     */
    public function emitir(NotaCredito $nota, ?int $almacenId = null): NotaCredito
    {
        if (! $nota->estado->esEditable()) {
            throw new RuntimeException(
                "La nota {$nota->numero_nota} esta {$nota->estado->label()} y ya fue emitida."
            );
        }

        $nota->load('lineas.producto', 'factura');

        if ($nota->lineas->isEmpty()) {
            throw new RuntimeException('Una nota de credito sin lineas no se puede emitir.');
        }

        $devuelveMercancia = $nota->motivo->regresaMercancia();

        if ($devuelveMercancia) {
            $almacenId ??= $this->almacenDelPedido($nota);

            if ($almacenId === null) {
                throw new RuntimeException(
                    'Elige a que almacen regresa la mercancia: la factura no viene de un pedido con almacen.'
                );
            }
        }

        return DB::transaction(function () use ($nota, $devuelveMercancia, $almacenId): NotaCredito {
            if ($devuelveMercancia) {
                foreach ($nota->lineas as $linea) {
                    if ($linea->producto_id === null || ! $linea->producto?->es_inventariable) {
                        continue;
                    }

                    $this->movimientos->registrar(
                        TipoMovimiento::DevolucionEntrada,
                        (int) $linea->producto_id,
                        (int) $almacenId,
                        (float) $linea->cantidad,
                        (float) $linea->producto->costo,
                        $nota,
                        ['organizacion_id' => $nota->organizacion_id],
                    );
                }
            }

            $nota->estado = EstadoNotaCredito::Emitida;
            $nota->save();

            $this->contabilizador->contabilizar('nota_credito', $nota, [
                'subtotal' => (float) $nota->subtotal,
                'impuesto' => (float) $nota->total_impuesto,
                'total' => (float) $nota->total,
                'cliente_id' => $nota->cliente_id,
                'factura_id' => $nota->factura_id,
                'motivo' => $nota->motivo->value,
            ]);

            $nota->registrarBitacora('emitida', [], [
                'estado' => EstadoNotaCredito::Emitida->value,
                'total' => (float) $nota->total,
                'devolvio_mercancia' => $devuelveMercancia,
            ]);

            return $nota->refresh();
        });
    }

    /**
     * Cancela la nota. Si ya estaba emitida con devolucion, la mercancia vuelve
     * a salir con el movimiento contrario.
     *
     * @throws RuntimeException
     */
    public function cancelar(NotaCredito $nota): NotaCredito
    {
        if (! $nota->estado->esCancelable()) {
            throw new RuntimeException("La nota {$nota->numero_nota} ya esta cancelada.");
        }

        return DB::transaction(function () use ($nota): NotaCredito {
            $estabaEmitida = $nota->estado === EstadoNotaCredito::Emitida;

            if ($estabaEmitida) {
                $this->movimientos->revertirDocumento($nota);
                $this->contabilizador->reversar('nota_credito', $nota, ['total' => (float) $nota->total]);
            }

            $nota->estado = EstadoNotaCredito::Cancelada;
            $nota->save();
            $nota->registrarBitacora('cancelada', [], [
                'estado' => EstadoNotaCredito::Cancelada->value,
                'movimientos_revertidos' => $estabaEmitida,
            ]);

            return $nota->refresh();
        });
    }

    /**
     * El almacen del que salio la mercancia, deducido del pedido de la factura.
     *
     * La mercancia vuelve de donde salio, que es lo que espera cualquier
     * almacenista. Si la factura no viene de un pedido, hay que elegirlo a mano.
     */
    private function almacenDelPedido(NotaCredito $nota): ?int
    {
        $almacenId = $nota->factura?->pedido?->lineas()
            ->whereNotNull('almacen_id')
            ->value('almacen_id');

        return $almacenId !== null ? (int) $almacenId : null;
    }

    /** @throws RuntimeException */
    private function verificarNoAcreditarDeMas(FacturaLinea $facturaLinea, float $cantidad): void
    {
        $facturada = (float) $facturaLinea->cantidad;

        $yaAcreditada = (float) NotaCreditoLinea::query()
            ->where('factura_linea_id', $facturaLinea->id)
            ->whereHas('notaCredito', fn ($consulta) => $consulta->whereIn('estado', ['borrador', 'emitida']))
            ->sum('cantidad');

        if ($yaAcreditada + $cantidad > $facturada + 0.000001) {
            throw new RuntimeException(sprintf(
                'No se puede acreditar mas de lo facturado: de %s se facturaron %s y ya hay %s en notas de credito.',
                $facturaLinea->producto?->nombre ?? $facturaLinea->descripcion ?? 'ese producto',
                rtrim(rtrim(number_format($facturada, 6, '.', ''), '0'), '.'),
                rtrim(rtrim(number_format($yaAcreditada, 6, '.', ''), '0'), '.'),
            ));
        }
    }
}
