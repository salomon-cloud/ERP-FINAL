<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Actividad;
use App\Modules\CRM\Models\Oportunidad;
use App\Modules\CRM\Models\Prospecto;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        return view('crm::reportes.index');
    }

    public function embudo(): View
    {
        $embudo = Oportunidad::query()
            ->selectRaw('etapa, COUNT(*) as total, COALESCE(SUM(monto), 0) as monto')
            ->groupBy('etapa')
            ->orderBy('etapa')
            ->get();

        return view('crm::reportes.embudo', compact('embudo'));
    }

    public function prospectos(): View
    {
        $prospectos = Prospecto::query()->latest('id')->limit(50)->get();

        return view('crm::reportes.prospectos', compact('prospectos'));
    }

    public function actividades(): View
    {
        $actividades = Actividad::query()->latest('id')->limit(50)->get();

        return view('crm::reportes.actividades', compact('actividades'));
    }
}