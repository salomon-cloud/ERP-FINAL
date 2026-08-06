<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Permiso;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }

    public function empleados(Request $request)
    {
        $items = Empleado::with(['departamento', 'puesto'])
            ->when($request->departamento_id, fn ($q, $id) => $q->where('departamento_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();
        return view('reportes.empleados', ['items' => $items, 'departamentos' => Departamento::all()]);
    }

    public function nominas(Request $request)
    {
        $items = Nomina::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->desde, fn ($q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha))->paginate(15)->withQueryString();
        return view('reportes.nominas', ['items' => $items, 'empleados' => Empleado::all()]);
    }

    public function asistencias(Request $request)
    {
        $items = Asistencia::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->desde, fn ($q, $fecha) => $q->whereDate('fecha', '>=', $fecha))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha', '<=', $fecha))->paginate(15)->withQueryString();
        return view('reportes.asistencias', ['items' => $items, 'empleados' => Empleado::all()]);
    }

    public function permisos(Request $request)
    {
        $items = Permiso::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();
        return view('reportes.permisos', ['items' => $items, 'empleados' => Empleado::all()]);
    }

    public function departamentos(Request $request)
    {
        $items = Departamento::withCount(['empleados', 'puestos'])
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();
        return view('reportes.departamentos', compact('items'));
    }

    public function pagosPendientes(Request $request)
    {
        $items = Nomina::with('empleado')->where('estado', 'pendiente')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha))->paginate(15)->withQueryString();
        return view('reportes.pagos-pendientes', ['items' => $items, 'empleados' => Empleado::all()]);
    }
}
