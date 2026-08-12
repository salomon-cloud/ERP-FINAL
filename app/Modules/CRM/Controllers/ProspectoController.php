<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Prospecto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProspectoController extends Controller
{
    public function index(Request $request): View
    {
        $prospectos = Prospecto::query()
            ->with('asignadoA')
            ->when($request->filled('estado'), fn ($consulta) => $consulta->where('estado', $request->input('estado')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('crm::paginas.prospectos.index', compact('prospectos'));
    }

    public function show(Prospecto $prospecto): View
    {
        $prospecto->load(['asignadoA', 'oportunidades', 'contactos']);

        return view('crm::paginas.prospectos.show', compact('prospecto'));
    }
}