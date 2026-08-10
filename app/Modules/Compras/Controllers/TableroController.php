<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Enums\EstadoRequisicion;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Requisicion;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * El tablero de Compras. Sustituye la pagina provisional conservando el nombre
 * `compras.dashboard`, que es al que apunta la barra lateral.
 *
 * Los cuatro KPI responden lo que un comprador pregunta al llegar: que tengo
 * que autorizar, que estoy esperando, que debo y que se me vence.
 */
class TableroController extends Controller
{
    public function __invoke(): View
    {
        return view('compras::dashboard.index', [
            'requisicionesPorRevisar' => Requisicion::where('estado', EstadoRequisicion::Enviada)->count(),
            'ordenesPendientes' => OrdenCompra::whereIn('estado', [
                EstadoOrdenCompra::Confirmada, EstadoOrdenCompra::RecibidaParcial,
            ])->count(),
            'porPagar' => (float) FacturaProveedor::whereIn('estado', [
                EstadoFacturaProveedor::Contabilizada, EstadoFacturaProveedor::PagadaParcial,
            ])->selectRaw('COALESCE(SUM(total - total_pagado), 0) as saldo')->value('saldo'),
            'vencidas' => FacturaProveedor::whereIn('estado', [
                EstadoFacturaProveedor::Contabilizada, EstadoFacturaProveedor::PagadaParcial,
            ])->whereDate('fecha_vencimiento', '<', Carbon::today())->count(),

            'solicitudesPendientes' => Requisicion::with(['departamento', 'solicitante'])
                ->where('estado', EstadoRequisicion::Enviada)
                ->orderBy('fecha_requerida')
                ->limit(8)
                ->get(),
            'ordenesPorRecibir' => OrdenCompra::with('proveedor')
                ->whereIn('estado', [EstadoOrdenCompra::Confirmada, EstadoOrdenCompra::RecibidaParcial])
                ->orderBy('fecha_entrega')
                ->limit(8)
                ->get(),
            'facturasPorPagar' => FacturaProveedor::with('proveedor')
                ->whereIn('estado', [EstadoFacturaProveedor::Contabilizada, EstadoFacturaProveedor::PagadaParcial])
                ->orderBy('fecha_vencimiento')
                ->limit(8)
                ->get(),
        ]);
    }
}
