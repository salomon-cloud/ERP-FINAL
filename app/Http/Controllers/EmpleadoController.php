<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Puesto;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $empleados = Empleado::with(['departamento', 'puesto'])
            ->when(auth()->user()->role === 'Empleado', fn ($q) => $q->where('id', auth()->user()->empleado_id))
            ->when($request->buscar, fn ($q, $buscar) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%$buscar%")->orWhere('apellidos', 'like', "%$buscar%")
                ->orWhere('curp', 'like', "%$buscar%")->orWhere('rfc', 'like', "%$buscar%")))
            ->latest()->paginate(10)->withQueryString();

        return view('empleados.index', compact('empleados'));
    }

    public function create()
    {
        $this->denyEmployeeRole();
        return view('empleados.create', ['empleado' => new Empleado(), 'departamentos' => Departamento::all(), 'puestos' => Puesto::all()]);
    }

    public function store(Request $request)
    {
        $this->denyEmployeeRole();
        $data = $this->validated($request);
        if ($request->hasFile('fotografia')) {
            $data['fotografia'] = $request->file('fotografia')->store('empleados', 'public');
        }
        Empleado::create($data);

        return redirect()->route('empleados.index')->with('success', 'Empleado registrado correctamente.');
    }

    public function show(Empleado $empleado)
    {
        $this->authorizeEmployee($empleado);
        $empleado->load(['departamento', 'puesto', 'nominas', 'asistencias', 'permisos']);
        return view('empleados.show', compact('empleado'));
    }

    public function edit(Empleado $empleado)
    {
        $this->denyEmployeeRole();
        return view('empleados.edit', ['empleado' => $empleado, 'departamentos' => Departamento::all(), 'puestos' => Puesto::all()]);
    }

    public function update(Request $request, Empleado $empleado)
    {
        $this->denyEmployeeRole();
        $data = $this->validated($request, $empleado->id);
        if ($request->hasFile('fotografia')) {
            $data['fotografia'] = $request->file('fotografia')->store('empleados', 'public');
        }
        $empleado->update($data);

        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $this->denyEmployeeRole();
        $empleado->delete();
        return redirect()->route('empleados.index')->with('success', 'Empleado eliminado correctamente.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'puesto_id' => ['required', 'exists:puestos,id'],
            'nombre' => ['required', 'max:120'],
            'apellidos' => ['required', 'max:160'],
            'curp' => ['required', 'size:18', 'unique:empleados,curp,' . $id],
            'rfc' => ['required', 'min:12', 'max:13', 'unique:empleados,rfc,' . $id],
            'correo' => ['required', 'email', 'unique:empleados,correo,' . $id],
            'telefono' => ['nullable', 'max:30'],
            'direccion' => ['nullable'],
            'fecha_nacimiento' => ['required', 'date'],
            'fecha_contratacion' => ['required', 'date'],
            'sueldo_base' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', 'in:activo,inactivo'],
            'fotografia' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function authorizeEmployee(Empleado $empleado): void
    {
        if (auth()->user()->role === 'Empleado' && auth()->user()->empleado_id !== $empleado->id) {
            abort(403);
        }
    }

    private function denyEmployeeRole(): void
    {
        if (auth()->user()->role === 'Empleado') {
            abort(403);
        }
    }
}
