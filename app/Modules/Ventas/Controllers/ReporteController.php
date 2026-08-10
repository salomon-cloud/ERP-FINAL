<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\ExportadorCsv;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\FacturaLinea;
use App\Modules\Ventas\Models\NotaCredito;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los reportes de Ventas.
 *
 * Todos de solo lectura y todos con `?formato=csv`, que descarga exactamente lo
 * que se ve en pantalla.
 */
class ReporteController extends Controller
{
    public function index(): View
    {
        return view('ventas::paginas.reportes.index');
    }

    /** Lo vendido en un periodo, factura por factura. */
    public function porPeriodo(Request $peticion): View|StreamedResponse
    {
        $filas = Factura::query()
            ->with('cliente')
            ->where('estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '<=', $peticion->date('hasta')))
            ->orderBy('fecha_emision')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('ventas', 'por-periodo',
                ['Folio', 'Fecha', 'Cliente', 'Subtotal', 'Impuesto', 'Total', 'Cobrado', 'Saldo', 'Estado'],
                $filas->map(fn (Factura $f) => [
                    $f->numero_factura,
                    $f->fecha_emision?->format('d/m/Y'),
                    $f->cliente?->nombre,
                    (float) $f->subtotal,
                    (float) $f->total_impuesto,
                    (float) $f->total,
                    (float) $f->total_cobrado,
                    $f->saldo,
                    $f->estado->label(),
                ]));
        }

        return view('ventas::paginas.reportes.por-periodo', [
            'filas' => $filas,
            'total' => $filas->sum(fn (Factura $f) => (float) $f->total),
            'cobrado' => $filas->sum(fn (Factura $f) => (float) $f->total_cobrado),
        ]);
    }

    /** Cuanto se le vendio a cada cliente. */
    public function porCliente(Request $peticion): View|StreamedResponse
    {
        $filas = Factura::query()
            ->join('clientes', 'clientes.id', '=', 'facturas.cliente_id')
            ->whereNull('facturas.deleted_at')
            ->where('facturas.estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('facturas.fecha_emision', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('facturas.fecha_emision', '<=', $peticion->date('hasta')))
            ->groupBy('clientes.id', 'clientes.codigo', 'clientes.nombre')
            ->selectRaw('clientes.codigo, clientes.nombre')
            ->selectRaw('COUNT(*) as facturas')
            ->selectRaw('SUM(facturas.total) as total')
            ->selectRaw('SUM(facturas.total - facturas.total_cobrado) as saldo')
            ->orderByDesc('total')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('ventas', 'por-cliente',
                ['Codigo', 'Cliente', 'Facturas', 'Total', 'Saldo'],
                $filas->map(fn ($f) => [$f->codigo, $f->nombre, $f->facturas, (float) $f->total, (float) $f->saldo]));
        }

        return view('ventas::paginas.reportes.por-cliente', [
            'filas' => $filas,
            'total' => $filas->sum(fn ($f) => (float) $f->total),
        ]);
    }

    /** Que producto deja mas dinero. */
    public function porProducto(Request $peticion): View|StreamedResponse
    {
        $filas = FacturaLinea::query()
            ->join('facturas as f', 'f.id', '=', 'factura_lineas.factura_id')
            ->join('productos as p', 'p.id', '=', 'factura_lineas.producto_id')
            ->whereNull('f.deleted_at')
            ->where('f.estado', '<>', 'cancelada')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('f.fecha_emision', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('f.fecha_emision', '<=', $peticion->date('hasta')))
            ->groupBy('p.id', 'p.sku', 'p.nombre')
            ->selectRaw('p.sku, p.nombre')
            ->selectRaw('SUM(factura_lineas.cantidad) as piezas')
            ->selectRaw('SUM(factura_lineas.subtotal) as importe')
            ->selectRaw('SUM(factura_lineas.subtotal) / NULLIF(SUM(factura_lineas.cantidad), 0) as precio_promedio')
            ->orderByDesc('importe')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('ventas', 'por-producto',
                ['SKU', 'Producto', 'Piezas', 'Importe', 'Precio promedio'],
                $filas->map(fn ($f) => [$f->sku, $f->nombre, (float) $f->piezas, (float) $f->importe, (float) $f->precio_promedio]));
        }

        return view('ventas::paginas.reportes.por-producto', ['filas' => $filas]);
    }

    /** Lo que nos deben, ordenado por vencimiento. */
    public function cuentasPorCobrar(Request $peticion): View|StreamedResponse
    {
        $filas = Factura::query()
            ->with('cliente')
            ->whereIn('estado', ['emitida', 'cobrada_parcial', 'vencida'])
            ->when($peticion->filled('cliente_id'),
                fn ($consulta) => $consulta->where('cliente_id', $peticion->integer('cliente_id')))
            ->orderBy('fecha_vencimiento')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('ventas', 'cuentas-por-cobrar',
                ['Folio', 'Cliente', 'Emision', 'Vencimiento', 'Total', 'Cobrado', 'Saldo', 'Dias vencida'],
                $filas->map(fn (Factura $f) => [
                    $f->numero_factura,
                    $f->cliente?->nombre,
                    $f->fecha_emision?->format('d/m/Y'),
                    $f->fecha_vencimiento?->format('d/m/Y'),
                    (float) $f->total,
                    (float) $f->total_cobrado,
                    $f->saldo,
                    $f->fecha_vencimiento !== null
                        ? (int) $f->fecha_vencimiento->diffInDays(now()->startOfDay(), false)
                        : 0,
                ]));
        }

        return view('ventas::paginas.reportes.cuentas-por-cobrar', [
            'filas' => $filas,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'saldoTotal' => $filas->sum(fn (Factura $f) => $f->saldo),
        ]);
    }

    /** Devoluciones y descuentos posteriores, agrupados por motivo. */
    public function devoluciones(Request $peticion): View|StreamedResponse
    {
        $notas = NotaCredito::query()
            ->with(['cliente', 'factura'])
            ->where('estado', 'emitida')
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '<=', $peticion->date('hasta')))
            ->orderByDesc('fecha_emision')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('ventas', 'devoluciones',
                ['Folio', 'Fecha', 'Cliente', 'Factura', 'Motivo', 'Total'],
                $notas->map(fn (NotaCredito $n) => [
                    $n->numero_nota,
                    $n->fecha_emision?->format('d/m/Y'),
                    $n->cliente?->nombre,
                    $n->factura?->numero_factura,
                    $n->motivo->label(),
                    (float) $n->total,
                ]));
        }

        return view('ventas::paginas.reportes.devoluciones', [
            'filas' => $notas,
            'porMotivo' => $notas->groupBy(fn (NotaCredito $n) => $n->motivo->label())
                ->map(fn ($grupo) => (object) [
                    'notas' => $grupo->count(),
                    'total' => $grupo->sum(fn (NotaCredito $n) => (float) $n->total),
                ]),
            'total' => $notas->sum(fn (NotaCredito $n) => (float) $n->total),
        ]);
    }
}
