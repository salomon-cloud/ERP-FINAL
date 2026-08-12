<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Actividad;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActividadController extends Controller
{
    public function index(Request $request): View
    {
        $actividades = Actividad::query()
            ->with('asignadaA')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('crm::paginas.actividades.index', compact('actividades'));
    }

    public function show(Actividad $actividad): View
    {
        $actividad->load('asignadaA');

        return view('crm::paginas.actividades.show', compact('actividad'));
    }
}