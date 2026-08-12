<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Oportunidad;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OportunidadController extends Controller
{
    public function index(Request $request): View
    {
        $oportunidades = Oportunidad::query()
            ->with('asignadoA')
            ->when($request->filled('etapa'), fn ($consulta) => $consulta->where('etapa', $request->input('etapa')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('crm::paginas.oportunidades.index', compact('oportunidades'));
    }

    public function show(Oportunidad $oportunidad): View
    {
        $oportunidad->load(['asignadoA']);

        return view('crm::paginas.oportunidades.show', compact('oportunidad'));
    }
}