<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Enums\EstadoCotizacion;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Enums\EstadoPedido;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\Pedido;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * El tablero de Ventas. Sustituye la pagina provisional conservando el nombre
 * `ventas.dashboard`, que es al que apunta la barra lateral.
 *
 * Los KPI responden lo que un vendedor pregunta al llegar: cuanto vendi este
 * mes, que tengo que surtir, que me deben y que se esta venciendo.
 */
class TableroController extends Controller
{
    public function __invoke(): View
    {
        $inicioDeMes = Carbon::today()->startOfMonth();

        return view('ventas::dashboard.index', [
            'ventasDelMes' => (float) Factura::query()
                ->where('estado', '<>', EstadoFactura::Cancelada)
                ->whereDate('fecha_emision', '>=', $inicioDeMes)
                ->sum('total'),
            'porSurtir' => Pedido::where('estado', EstadoPedido::Confirmado)->count(),
            'porCobrar' => (float) Factura::query()
                ->whereIn('estado', [EstadoFactura::Emitida, EstadoFactura::CobradaParcial])
                ->selectRaw('COALESCE(SUM(total - total_cobrado), 0) as saldo')
                ->value('saldo'),
            'vencidas' => Factura::query()
                ->whereIn('estado', [EstadoFactura::Emitida, EstadoFactura::CobradaParcial])
                ->whereDate('fecha_vencimiento', '<', Carbon::today())
                ->count(),

            'pedidosPorSurtir' => Pedido::with('cliente')
                ->where('estado', EstadoPedido::Confirmado)
                ->orderBy('fecha_entrega')
                ->limit(8)
                ->get(),
            'cotizacionesAbiertas' => Cotizacion::with('cliente')
                ->whereIn('estado', [EstadoCotizacion::Enviada, EstadoCotizacion::Aceptada])
                ->orderBy('vigencia')
                ->limit(8)
                ->get(),
            'facturasPorCobrar' => Factura::with('cliente')
                ->whereIn('estado', [EstadoFactura::Emitida, EstadoFactura::CobradaParcial])
                ->orderBy('fecha_vencimiento')
                ->limit(8)
                ->get(),
        ]);
    }
}
