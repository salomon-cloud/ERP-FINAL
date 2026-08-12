<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Actividad;
use App\Modules\CRM\Models\Oportunidad;
use App\Modules\CRM\Models\Prospecto;
use App\Modules\CRM\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableroController extends Controller
{
    public function __invoke(Request $peticion): View
    {
        return view('crm::dashboard.tablero', [
            'prospectosNuevos' => Prospecto::query()->where('estado', 'nuevo')->count(),
            'oportunidadesAbiertas' => Oportunidad::query()->whereNotIn('etapa', ['ganada', 'perdida'])->count(),
            'montoEmbudo' => (float) Oportunidad::query()->whereNotIn('etapa', ['ganada', 'perdida'])->sum('monto'),
            'tareasPendientes' => Tarea::query()->where('estado', 'pendiente')->count(),
            'actividadesRecientes' => Actividad::query()->latest('id')->limit(8)->get(),
            'oportunidadesPorEtapa' => Oportunidad::query()
                ->selectRaw('etapa, COUNT(*) as total, COALESCE(SUM(monto), 0) as monto')
                ->groupBy('etapa')
                ->orderBy('etapa')
                ->get(),
        ]);
    }
}