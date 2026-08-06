<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Puesto;
use Illuminate\Http\Request;

class PuestoController extends Controller
{
    public function index(Request $request)
    {
        $puestos = Puesto::with('departamento')->withCount('empleados')
            ->when($request->buscar, fn ($q, $buscar) => $q->where('nombre', 'like', "%$buscar%"))
            ->when($request->departamento_id, fn ($q, $id) => $q->where('departamento_id', $id))
            ->latest()->paginate(10)->withQueryString();
        return view('puestos.index', ['puestos' => $puestos, 'departamentos' => Departamento::all()]);
    }

    public function create() { return view('puestos.create', ['puesto' => new Puesto(), 'departamentos' => Departamento::all()]); }
    public function show(Puesto $puesto) { $puesto->load(['departamento', 'empleados']); return view('puestos.show', compact('puesto')); }
    public function edit(Puesto $puesto) { return view('puestos.edit', ['puesto' => $puesto, 'departamentos' => Departamento::all()]); }
    public function store(Request $request) { Puesto::create($this->validated($request)); return redirect()->route('puestos.index')->with('success', 'Puesto creado correctamente.'); }
    public function update(Request $request, Puesto $puesto) { $puesto->update($this->validated($request)); return redirect()->route('puestos.index')->with('success', 'Puesto actualizado correctamente.'); }
    public function destroy(Puesto $puesto) { $puesto->delete(); return redirect()->route('puestos.index')->with('success', 'Puesto eliminado correctamente.'); }

    private function validated(Request $request): array
    {
        return $request->validate([
            'departamento_id' => ['required', 'exists:departamentos,id'], 'nombre' => ['required', 'max:120'], 'descripcion' => ['nullable'],
            'sueldo_minimo' => ['required', 'numeric', 'min:0'], 'sueldo_maximo' => ['required', 'numeric', 'gte:sueldo_minimo'], 'estado' => ['required', 'in:activo,inactivo'],
        ]);
    }
}
