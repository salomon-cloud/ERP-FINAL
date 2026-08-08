<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Requests\GuardarDepartamentoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartamentoController extends Controller
{
    public function index(Request $peticion): View
    {
        $departamentos = Departamento::query()
            ->with(['padre', 'jefe'])
            ->withCount('empleados')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'), fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('rh::catalogos.departamentos.index', [
            'departamentos' => $departamentos,
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::catalogos.departamentos.create', $this->datosDelFormulario(new Departamento));
    }

    public function store(GuardarDepartamentoRequest $peticion): RedirectResponse
    {
        Departamento::create($peticion->validated());

        return redirect()->route('rh.departamentos.index')
            ->with('success', 'Departamento registrado correctamente.');
    }

    public function show(Departamento $departamento): View
    {
        $departamento->load(['padre', 'jefe', 'hijos', 'puestos']);

        return view('rh::catalogos.departamentos.show', ['departamento' => $departamento]);
    }

    public function edit(Departamento $departamento): View
    {
        return view('rh::catalogos.departamentos.edit', $this->datosDelFormulario($departamento));
    }

    public function update(GuardarDepartamentoRequest $peticion, Departamento $departamento): RedirectResponse
    {
        $departamento->update($peticion->validated());

        return redirect()->route('rh.departamentos.index')
            ->with('success', 'Departamento actualizado correctamente.');
    }

    public function destroy(Departamento $departamento): RedirectResponse
    {
        $departamento->delete();

        return redirect()->route('rh.departamentos.index')
            ->with('success', 'Departamento eliminado correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Departamento $departamento): array
    {
        return [
            'departamento' => $departamento,
            // Un departamento no puede colgar de si mismo.
            'padres' => Departamento::query()
                ->whereKeyNot($departamento->getKey())
                ->orderBy('nombre')
                ->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }
}
