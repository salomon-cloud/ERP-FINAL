<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\FacturaProveedorLinea;
use App\Modules\Compras\Models\OrdenCompraLinea;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La factura del proveedor y su COTEJO DE TRES VIAS.
 *
 * QUE ES EL COTEJO DE TRES VIAS Y POR QUE IMPORTA
 *
 * Antes de pagarle a nadie se comparan tres documentos que deberian decir lo
 * mismo: lo que se PIDIO (orden), lo que LLEGO (recepcion) y lo que se COBRA
 * (factura). Si el proveedor factura 100 piezas de las que solo llegaron 80, o
 * factura algo que nunca se pidio, el cotejo lo detiene aqui y no tres meses
 * despues en una conciliacion.
 *
 * La comprobacion es por linea contra `orden_compra_lineas.cantidad_recibida`,
 * con la misma tolerancia de configuracion que la recepcion.
 *
 * DEPENDENCIA DE FINANZAS: contabilizar deberia generar la poliza de CxP +
 * gasto + IVA acreditable. Mientras Finanzas no publique su servicio, el
 * Contabilizador de contingencia deja constancia en la bitacora y la factura
 * avanza igual a `contabilizada` (docs/david.md §33). El dia que exista, se
 * cambia el binding y este servicio no se toca.
 */
class ServicioFacturaProveedor
{
    public function __construct(
        private readonly Contabilizador $contabilizador,
        private readonly ServicioOrdenCompra $ordenes,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la factura ya no es editable
     */
    public function agregarLinea(FacturaProveedor $factura, array $datos): FacturaProveedorLinea
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} esta {$factura->estado->label()} y ya no admite cambios."
            );
        }

        return DB::transaction(function () use ($factura, $datos): FacturaProveedorLinea {
            $tasa = $this->tasaDelImpuesto($datos['impuesto_id'] ?? null);

            $totales = CalculadoraLinea::calcular(
                (float) $datos['cantidad'],
                (float) $datos['costo_unitario'],
                0,
                0,
                $tasa,
            );

            $linea = $factura->lineas()->create([
                'orden_compra_linea_id' => $datos['orden_compra_linea_id'] ?? null,
                'producto_id' => $datos['producto_id'] ?? null,
                'descripcion' => $datos['descripcion'] ?? null,
                'cantidad' => $datos['cantidad'],
                'costo_unitario' => $datos['costo_unitario'],
                'impuesto_id' => $datos['impuesto_id'] ?? null,
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
    public function eliminarLinea(FacturaProveedor $factura, FacturaProveedorLinea $linea): void
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
     * Copia a la factura lo que ya se recibio de la orden.
     *
     * Es el atajo normal: el proveedor factura lo que entrego, asi que partir de
     * lo recibido evita capturar de nuevo veinte renglones y evita el error mas
     * comun, que es facturar lo pedido en vez de lo llegado.
     *
     * @throws RuntimeException si la factura no tiene orden o no es editable
     */
    public function copiarDesdeOrden(FacturaProveedor $factura): int
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException("La factura {$factura->numero_factura} ya no admite cambios.");
        }

        $orden = $factura->ordenCompra;

        if ($orden === null) {
            throw new RuntimeException('Esta factura no esta ligada a una orden de compra.');
        }

        $copiadas = 0;

        foreach ($orden->lineas as $linea) {
            $recibida = (float) $linea->cantidad_recibida;

            if ($recibida <= 0) {
                continue;
            }

            $this->agregarLinea($factura, [
                'orden_compra_linea_id' => $linea->id,
                'producto_id' => $linea->producto_id,
                'descripcion' => $linea->descripcion,
                'cantidad' => $recibida,
                'costo_unitario' => (float) $linea->costo_unitario,
                'impuesto_id' => $linea->impuesto_id,
            ]);

            $copiadas++;
        }

        if ($copiadas === 0) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} todavia no tiene nada recibido que facturar."
            );
        }

        return $copiadas;
    }

    /**
     * Cotejo de tres vias + contabilizacion.
     *
     * @throws RuntimeException si el cotejo falla o el estado no lo permite
     */
    public function contabilizar(FacturaProveedor $factura, int $versionFila): FacturaProveedor
    {
        if (! $factura->estado->esEditable()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} esta {$factura->estado->label()} y ya fue contabilizada."
            );
        }

        $factura->load(['lineas.ordenCompraLinea', 'ordenCompra']);

        if ($factura->lineas->isEmpty()) {
            throw new RuntimeException('Una factura sin lineas no se puede contabilizar.');
        }

        $diferencias = $this->cotejarTresVias($factura);

        if ($diferencias !== []) {
            throw new RuntimeException(
                'El cotejo de tres vias no cuadra: '.implode(' ', $diferencias)
            );
        }

        return DB::transaction(function () use ($factura, $versionFila): FacturaProveedor {
            $afectadas = FacturaProveedor::query()
                ->whereKey($factura->getKey())
                ->where('version_fila', $versionFila)
                ->update([
                    'estado' => EstadoFacturaProveedor::Contabilizada->value,
                    'version_fila' => $versionFila + 1,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                throw new RuntimeException(
                    'Alguien mas modifico esta factura mientras la revisabas. Vuelve a cargarla.'
                );
            }

            $factura->refresh();

            $this->contabilizador->contabilizar('factura_proveedor', $factura, [
                'subtotal' => (float) $factura->subtotal,
                'impuesto' => (float) $factura->total_impuesto,
                'total' => (float) $factura->total,
                'proveedor_id' => $factura->proveedor_id,
                'periodo_fiscal_id' => $factura->periodo_fiscal_id,
            ]);

            $factura->registrarBitacora('contabilizada', [], [
                'estado' => EstadoFacturaProveedor::Contabilizada->value,
                'total' => (float) $factura->total,
            ]);

            if ($factura->ordenCompra !== null) {
                $this->ordenes->marcarFacturada($factura->ordenCompra);
            }

            return $factura;
        });
    }

    /**
     * Cancela la factura y pide la reversa contable.
     *
     * Con pagos aplicados no se cancela: primero se cancelan los pagos, porque
     * cancelar una factura pagada dejaria un pago sin destino.
     *
     * @throws RuntimeException
     */
    public function cancelar(FacturaProveedor $factura): FacturaProveedor
    {
        if (! $factura->estado->esCancelable()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} esta {$factura->estado->label()} y ya no se puede cancelar."
            );
        }

        if ($factura->pagos()->where('estado', 'aplicado')->exists()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} tiene pagos aplicados. Cancelalos primero."
            );
        }

        if ($factura->devoluciones()->where('estado', 'aplicada')->exists()) {
            throw new RuntimeException(
                "La factura {$factura->numero_factura} tiene devoluciones aplicadas. Cancelalas primero."
            );
        }

        return DB::transaction(function () use ($factura): FacturaProveedor {
            $eraContabilizada = $factura->estado === EstadoFacturaProveedor::Contabilizada;

            $factura->estado = EstadoFacturaProveedor::Cancelada;
            $factura->save();

            if ($eraContabilizada) {
                $this->contabilizador->reversar('factura_proveedor', $factura, [
                    'total' => (float) $factura->total,
                ]);
            }

            $factura->registrarBitacora('cancelada', [], [
                'estado' => EstadoFacturaProveedor::Cancelada->value,
            ]);

            return $factura->refresh();
        });
    }

    /**
     * Compara la factura contra la orden y contra lo recibido.
     *
     * @return array<int, string> los problemas encontrados, vacio si cuadra
     */
    public function cotejarTresVias(FacturaProveedor $factura): array
    {
        // Sin orden de compra no hay contra que cotejar. Es legitimo -- un
        // gasto suelto, un servicio -- y no se bloquea: solo no hay cotejo.
        if ($factura->orden_compra_id === null) {
            return [];
        }

        $tolerancia = (float) config('sisen.inventory.over_receipt_tolerance', 0);
        $problemas = [];

        // Lo facturado por linea de orden, sumando por si el proveedor partio
        // un mismo renglon en dos.
        $facturadoPorLinea = $factura->lineas
            ->whereNotNull('orden_compra_linea_id')
            ->groupBy('orden_compra_linea_id')
            ->map(fn ($lineas) => (float) $lineas->sum('cantidad'));

        $sinReferencia = $factura->lineas->whereNull('orden_compra_linea_id')->count();

        if ($sinReferencia > 0) {
            $problemas[] = "{$sinReferencia} linea(s) no apuntan a ningun renglon de la orden ".
                "{$factura->ordenCompra->numero_orden}.";
        }

        foreach ($facturadoPorLinea as $ordenLineaId => $facturada) {
            $ordenLinea = OrdenCompraLinea::with('producto')->find($ordenLineaId);

            if ($ordenLinea === null) {
                continue;
            }

            $recibida = (float) $ordenLinea->cantidad_recibida;
            $maxima = $recibida * (1 + $tolerancia / 100);

            if ($facturada > $maxima + 0.000001) {
                $problemas[] = sprintf(
                    'De %s se facturan %s pero solo se recibieron %s.',
                    $ordenLinea->producto?->nombre ?? "el renglon #{$ordenLineaId}",
                    rtrim(rtrim(number_format($facturada, 6, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format($recibida, 6, '.', ''), '0'), '.'),
                );
            }
        }

        return $problemas;
    }

    /** La tasa del impuesto como fraccion. Ver ServicioOrdenCompra. */
    private function tasaDelImpuesto(?int $impuestoId): float
    {
        if ($impuestoId === null) {
            return 0.0;
        }

        return (float) (DB::table('impuestos')->where('id', $impuestoId)->value('tasa') ?? 0);
    }
}
