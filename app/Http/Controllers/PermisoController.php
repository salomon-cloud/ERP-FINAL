<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Permiso;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function index(Request $request)
    {
        $permisos = Permiso::with('empleado')
            ->when(auth()->user()->role === 'Empleado', fn ($q) => $q->where('empleado_id', auth()->user()->empleado_id))
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()->paginate(10)->withQueryString();
        return view('permisos.index', ['permisos' => $permisos, 'empleados' => Empleado::orderBy('nombre')->get()]);
    }

    public function create() { return view('permisos.create', ['permiso' => new Permiso(), 'empleados' => $this->availableEmployees()]); }
    public function show(Permiso $permiso) { $this->authorizeEmployee($permiso); $permiso->load('empleado'); return view('permisos.show', compact('permiso')); }
    public function edit(Permiso $permiso) { $this->authorizeEmployee($permiso); return view('permisos.edit', ['permiso' => $permiso, 'empleados' => $this->availableEmployees()]); }
    public function store(Request $request) { $data = $this->validated($request); if (auth()->user()->role === 'Empleado') $data['empleado_id'] = auth()->user()->empleado_id; Permiso::create($data); return redirect()->route('permisos.index')->with('success', 'Solicitud registrada correctamente.'); }
    public function update(Request $request, Permiso $permiso) { $this->authorizeEmployee($permiso); $data = $this->validated($request); if (auth()->user()->role === 'Empleado') { $data['empleado_id'] = auth()->user()->empleado_id; $data['estado'] = 'pendiente'; } $permiso->update($data); return redirect()->route('permisos.index')->with('success', 'Solicitud actualizada correctamente.'); }
    public function destroy(Permiso $permiso) { $this->authorizeEmployee($permiso); $permiso->delete(); return redirect()->route('permisos.index')->with('success', 'Solicitud eliminada correctamente.'); }
    public function approve(Permiso $permiso) { $permiso->update(['estado' => 'aprobado']); return back()->with('success', 'Solicitud aprobada.'); }
    public function reject(Permiso $permiso) { $permiso->update(['estado' => 'rechazado']); return back()->with('success', 'Solicitud rechazada.'); }

    private function validated(Request $request): array
    {
        return $request->validate(['empleado_id' => ['required', 'exists:empleados,id'], 'tipo' => ['required', 'in:permiso,vacaciones,incapacidad'], 'fecha_inicio' => ['required', 'date'], 'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'], 'motivo' => ['required'], 'estado' => ['required', 'in:pendiente,aprobado,rechazado']]);
    }

    private function authorizeEmployee(Permiso $permiso): void
    {
        if (auth()->user()->role === 'Empleado' && auth()->user()->empleado_id !== $permiso->empleado_id) abort(403);
    }

    private function availableEmployees()
    {
        return Empleado::where('estado', 'activo')
            ->when(auth()->user()->role === 'Empleado', fn ($q) => $q->where('id', auth()->user()->empleado_id))
            ->get();
    }
}
