<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoContrato;
use App\Modules\RH\Enums\TipoContrato;
use App\Modules\RH\Models\Contrato;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Requests\GuardarContratoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContratoController extends Controller
{
    public function index(Request $peticion): View
    {
        $contratos = Contrato::query()
            ->with('empleado')
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            // El filtro que alimenta el KPI "contratos por vencer" del tablero.
            ->when($peticion->boolean('por_vencer'), fn ($consulta) => $consulta->porVencer())
            ->orderByDesc('fecha_inicio')
            ->paginate(10)
            ->withQueryString();

        return view('rh::paginas.contratos.index', [
            'contratos' => $contratos,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoContrato::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.contratos.create', $this->datosDelFormulario(new Contrato));
    }

    public function store(GuardarContratoRequest $peticion): RedirectResponse
    {
        Contrato::create($peticion->validated());

        return redirect()->route('rh.contratos.index')
            ->with('success', 'Contrato registrado correctamente.');
    }

    public function show(Contrato $contrato): View
    {
        $contrato->load('empleado');

        return view('rh::paginas.contratos.show', ['contrato' => $contrato]);
    }

    public function edit(Contrato $contrato): View
    {
        return view('rh::paginas.contratos.edit', $this->datosDelFormulario($contrato));
    }

    public function update(GuardarContratoRequest $peticion, Contrato $contrato): RedirectResponse
    {
        $contrato->update($peticion->validated());

        return redirect()->route('rh.contratos.index')
            ->with('success', 'Contrato actualizado correctamente.');
    }

    public function destroy(Contrato $contrato): RedirectResponse
    {
        $contrato->delete();

        return redirect()->route('rh.contratos.index')
            ->with('success', 'Contrato eliminado correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Contrato $contrato): array
    {
        return [
            'contrato' => $contrato,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'tipos' => OpcionesEnum::de(TipoContrato::class),
            'estados' => OpcionesEnum::de(EstadoContrato::class),
        ];
    }
}
