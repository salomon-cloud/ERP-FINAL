<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $asistencias = Asistencia::with('empleado')
            ->when(auth()->user()->role === 'Empleado', fn ($q) => $q->where('empleado_id', auth()->user()->empleado_id))
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->fecha, fn ($q, $fecha) => $q->whereDate('fecha', $fecha))
            ->latest('fecha')->paginate(10)->withQueryString();
        return view('asistencias.index', ['asistencias' => $asistencias, 'empleados' => Empleado::orderBy('nombre')->get()]);
    }

    public function create() { $this->denyEmployeeRole(); return view('asistencias.create', ['asistencia' => new Asistencia(), 'empleados' => Empleado::where('estado', 'activo')->get()]); }
    public function show(Asistencia $asistencia) { $this->authorizeEmployee($asistencia); $asistencia->load('empleado'); return view('asistencias.show', compact('asistencia')); }
    public function edit(Asistencia $asistencia) { $this->denyEmployeeRole(); return view('asistencias.edit', ['asistencia' => $asistencia, 'empleados' => Empleado::where('estado', 'activo')->get()]); }
    public function store(Request $request) { $this->denyEmployeeRole(); Asistencia::create($this->validated($request)); return redirect()->route('asistencias.index')->with('success', 'Asistencia registrada correctamente.'); }
    public function update(Request $request, Asistencia $asistencia) { $this->denyEmployeeRole(); $asistencia->update($this->validated($request)); return redirect()->route('asistencias.index')->with('success', 'Asistencia actualizada correctamente.'); }
    public function destroy(Asistencia $asistencia) { $this->denyEmployeeRole(); $asistencia->delete(); return redirect()->route('asistencias.index')->with('success', 'Asistencia eliminada correctamente.'); }

    private function validated(Request $request): array
    {
        return $request->validate(['empleado_id' => ['required', 'exists:empleados,id'], 'fecha' => ['required', 'date'], 'hora_entrada' => ['nullable'], 'hora_salida' => ['nullable'], 'estado' => ['required', 'in:presente,falta,retardo,permiso']]);
    }

    private function authorizeEmployee(Asistencia $asistencia): void
    {
        if (auth()->user()->role === 'Empleado' && auth()->user()->empleado_id !== $asistencia->empleado_id) abort(403);
    }

    private function denyEmployeeRole(): void
    {
        if (auth()->user()->role === 'Empleado') abort(403);
    }
}
