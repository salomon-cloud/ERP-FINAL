<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\ExportadorCsv;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\FacturaProveedorLinea;
use App\Modules\Compras\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los reportes de Compras.
 *
 * Todos de solo lectura y todos con `?formato=csv`, que descarga exactamente lo
 * que se ve en pantalla: la consulta es una sola y solo cambia como se presenta.
 */
class ReporteController extends Controller
{
    public function index(): View
    {
        return view('compras::paginas.reportes.index');
    }

    /** Lo comprado en un periodo, factura por factura. */
    public function porPeriodo(Request $peticion): View|StreamedResponse
    {
        $filas = FacturaProveedor::query()
            ->with('proveedor')
            ->where('estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderBy('fecha')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('compras', 'por-periodo',
                ['Folio', 'Fecha', 'Proveedor', 'Subtotal', 'Impuesto', 'Total', 'Pagado', 'Saldo', 'Estado'],
                $filas->map(fn (FacturaProveedor $f) => [
                    $f->numero_factura,
                    $f->fecha?->format('d/m/Y'),
                    $f->proveedor?->nombre,
                    (float) $f->subtotal,
                    (float) $f->total_impuesto,
                    (float) $f->total,
                    (float) $f->total_pagado,
                    $f->saldo,
                    $f->estado->label(),
                ]));
        }

        return view('compras::paginas.reportes.por-periodo', [
            'filas' => $filas,
            'total' => $filas->sum(fn (FacturaProveedor $f) => (float) $f->total),
        ]);
    }

    /** Cuanto se le compro a cada proveedor. */
    public function porProveedor(Request $peticion): View|StreamedResponse
    {
        $filas = FacturaProveedor::query()
            ->join('proveedores', 'proveedores.id', '=', 'facturas_proveedor.proveedor_id')
            ->whereNull('facturas_proveedor.deleted_at')
            ->where('facturas_proveedor.estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('facturas_proveedor.fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('facturas_proveedor.fecha', '<=', $peticion->date('hasta')))
            ->groupBy('proveedores.id', 'proveedores.codigo', 'proveedores.nombre')
            ->selectRaw('proveedores.codigo, proveedores.nombre')
            ->selectRaw('COUNT(*) as facturas')
            ->selectRaw('SUM(facturas_proveedor.total) as total')
            ->selectRaw('SUM(facturas_proveedor.total - facturas_proveedor.total_pagado) as saldo')
            ->orderByDesc('total')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('compras', 'por-proveedor',
                ['Codigo', 'Proveedor', 'Facturas', 'Total', 'Saldo'],
                $filas->map(fn ($f) => [$f->codigo, $f->nombre, $f->facturas, (float) $f->total, (float) $f->saldo]));
        }

        return view('compras::paginas.reportes.por-proveedor', [
            'filas' => $filas,
            'total' => $filas->sum(fn ($f) => (float) $f->total),
        ]);
    }

    /** Que producto se compra mas y a que costo promedio. */
    public function porProducto(Request $peticion): View|StreamedResponse
    {
        $filas = FacturaProveedorLinea::query()
            ->join('facturas_proveedor as f', 'f.id', '=', 'factura_proveedor_lineas.factura_proveedor_id')
            ->join('productos as p', 'p.id', '=', 'factura_proveedor_lineas.producto_id')
            ->whereNull('f.deleted_at')
            ->where('f.estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('f.fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('f.fecha', '<=', $peticion->date('hasta')))
            ->groupBy('p.id', 'p.sku', 'p.nombre')
            ->selectRaw('p.sku, p.nombre')
            ->selectRaw('SUM(factura_proveedor_lineas.cantidad) as piezas')
            ->selectRaw('SUM(factura_proveedor_lineas.subtotal) as importe')
            ->selectRaw('SUM(factura_proveedor_lineas.subtotal) / NULLIF(SUM(factura_proveedor_lineas.cantidad), 0) as costo_promedio')
            ->orderByDesc('importe')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('compras', 'por-producto',
                ['SKU', 'Producto', 'Piezas', 'Importe', 'Costo promedio'],
                $filas->map(fn ($f) => [$f->sku, $f->nombre, (float) $f->piezas, (float) $f->importe, (float) $f->costo_promedio]));
        }

        return view('compras::paginas.reportes.por-producto', ['filas' => $filas]);
    }

    /** Lo que se pidio y todavia no llega: la mercancia "en camino". */
    public function pendientesPorRecibir(Request $peticion): View|StreamedResponse
    {
        $filas = DB::table('orden_compra_lineas as l')
            ->join('ordenes_compra as o', 'o.id', '=', 'l.orden_compra_id')
            ->join('proveedores as pr', 'pr.id', '=', 'o.proveedor_id')
            ->leftJoin('productos as p', 'p.id', '=', 'l.producto_id')
            ->whereNull('o.deleted_at')
            ->whereIn('o.estado', ['confirmada', 'recibida_parcial'])
            ->whereRaw('l.cantidad > l.cantidad_recibida')
            ->when($peticion->filled('proveedor_id'),
                fn ($consulta) => $consulta->where('o.proveedor_id', $peticion->integer('proveedor_id')))
            ->selectRaw('o.numero_orden, o.fecha, o.fecha_entrega, pr.nombre as proveedor')
            ->selectRaw('p.sku, p.nombre as producto')
            ->selectRaw('l.cantidad, l.cantidad_recibida, (l.cantidad - l.cantidad_recibida) as pendiente')
            ->selectRaw('l.costo_unitario, (l.cantidad - l.cantidad_recibida) * l.costo_unitario as importe_pendiente')
            ->orderBy('o.fecha_entrega')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('compras', 'pendientes-por-recibir',
                ['Orden', 'Fecha', 'Entrega', 'Proveedor', 'SKU', 'Producto', 'Pedido', 'Recibido', 'Pendiente', 'Importe pendiente'],
                collect($filas)->map(fn ($f) => [
                    $f->numero_orden, $f->fecha, $f->fecha_entrega, $f->proveedor,
                    $f->sku, $f->producto, (float) $f->cantidad, (float) $f->cantidad_recibida,
                    (float) $f->pendiente, (float) $f->importe_pendiente,
                ]));
        }

        return view('compras::paginas.reportes.pendientes-por-recibir', [
            'filas' => collect($filas),
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'importeTotal' => collect($filas)->sum(fn ($f) => (float) $f->importe_pendiente),
        ]);
    }

    /** Lo que se debe, ordenado por vencimiento: la antiguedad de saldos. */
    public function cuentasPorPagar(Request $peticion): View|StreamedResponse
    {
        $filas = FacturaProveedor::query()
            ->with('proveedor')
            ->whereIn('estado', ['contabilizada', 'pagada_parcial'])
            ->when($peticion->filled('proveedor_id'),
                fn ($consulta) => $consulta->where('proveedor_id', $peticion->integer('proveedor_id')))
            ->orderBy('fecha_vencimiento')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('compras', 'cuentas-por-pagar',
                ['Folio', 'Proveedor', 'Fecha', 'Vencimiento', 'Total', 'Pagado', 'Saldo', 'Dias vencida'],
                $filas->map(fn (FacturaProveedor $f) => [
                    $f->numero_factura,
                    $f->proveedor?->nombre,
                    $f->fecha?->format('d/m/Y'),
                    $f->fecha_vencimiento?->format('d/m/Y'),
                    (float) $f->total,
                    (float) $f->total_pagado,
                    $f->saldo,
                    $this->diasVencida($f),
                ]));
        }

        return view('compras::paginas.reportes.cuentas-por-pagar', [
            'filas' => $filas,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'saldoTotal' => $filas->sum(fn (FacturaProveedor $f) => $f->saldo),
        ]);
    }

    /** Cuantos dias lleva vencida una factura. Negativo = todavia no vence. */
    private function diasVencida(FacturaProveedor $factura): int
    {
        if ($factura->fecha_vencimiento === null) {
            return 0;
        }

        return (int) $factura->fecha_vencimiento->diffInDays(now()->startOfDay(), false);
    }
}
