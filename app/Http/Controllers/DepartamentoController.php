<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function index(Request $request)
    {
        $departamentos = Departamento::withCount(['empleados', 'puestos'])
            ->when($request->buscar, fn ($q, $buscar) => $q->where('nombre', 'like', "%$buscar%"))
            ->latest()->paginate(10)->withQueryString();
        return view('departamentos.index', compact('departamentos'));
    }

    public function create() { return view('departamentos.create', ['departamento' => new Departamento()]); }
    public function show(Departamento $departamento) { $departamento->load(['empleados', 'puestos']); return view('departamentos.show', compact('departamento')); }
    public function edit(Departamento $departamento) { return view('departamentos.edit', compact('departamento')); }

    public function store(Request $request)
    {
        Departamento::create($this->validated($request));
        return redirect()->route('departamentos.index')->with('success', 'Departamento creado correctamente.');
    }

    public function update(Request $request, Departamento $departamento)
    {
        $departamento->update($this->validated($request));
        return redirect()->route('departamentos.index')->with('success', 'Departamento actualizado correctamente.');
    }

    public function destroy(Departamento $departamento)
    {
        $departamento->delete();
        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado correctamente.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['nombre' => ['required', 'max:120'], 'descripcion' => ['nullable'], 'responsable' => ['nullable', 'max:160'], 'estado' => ['required', 'in:activo,inactivo']]);
    }
}
