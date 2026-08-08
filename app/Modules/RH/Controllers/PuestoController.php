<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\Puesto;
use App\Modules\RH\Requests\GuardarPuestoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PuestoController extends Controller
{
    public function index(Request $peticion): View
    {
        $puestos = Puesto::query()
            ->with('departamento')
            ->withCount('empleados')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('departamento_id'),
                fn ($consulta) => $consulta->where('departamento_id', $peticion->integer('departamento_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('rh::catalogos.puestos.index', [
            'puestos' => $puestos,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::catalogos.puestos.create', $this->datosDelFormulario(new Puesto));
    }

    public function store(GuardarPuestoRequest $peticion): RedirectResponse
    {
        Puesto::create($peticion->validated());

        return redirect()->route('rh.puestos.index')
            ->with('success', 'Puesto registrado correctamente.');
    }

    public function show(Puesto $puesto): View
    {
        $puesto->load('departamento');

        return view('rh::catalogos.puestos.show', ['puesto' => $puesto]);
    }

    public function edit(Puesto $puesto): View
    {
        return view('rh::catalogos.puestos.edit', $this->datosDelFormulario($puesto));
    }

    public function update(GuardarPuestoRequest $peticion, Puesto $puesto): RedirectResponse
    {
        $puesto->update($peticion->validated());

        return redirect()->route('rh.puestos.index')
            ->with('success', 'Puesto actualizado correctamente.');
    }

    public function destroy(Puesto $puesto): RedirectResponse
    {
        $puesto->delete();

        return redirect()->route('rh.puestos.index')
            ->with('success', 'Puesto eliminado correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Puesto $puesto): array
    {
        return [
            'puesto' => $puesto,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }
}
