<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Permiso;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $empleadoId = $user->role === 'Empleado' ? $user->empleado_id : null;

        $nominasQuery = Nomina::query()->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId));
        $permisosQuery = Permiso::query()->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId));

        return view('dashboard.index', [
            'totalEmpleados' => Empleado::count(),
            'empleadosActivos' => Empleado::where('estado', 'activo')->count(),
            'empleadosInactivos' => Empleado::where('estado', 'inactivo')->count(),
            'totalDepartamentos' => Departamento::count(),
            'nominasGeneradas' => (clone $nominasQuery)->count(),
            'nominasPendientes' => (clone $nominasQuery)->where('estado', 'pendiente')->count(),
            'pagosRealizados' => (clone $nominasQuery)->where('estado', 'pagada')->sum('total_pagar'),
            'solicitudesPendientes' => (clone $permisosQuery)->where('estado', 'pendiente')->count(),
            'ultimasNominas' => (clone $nominasQuery)->with('empleado')->latest()->take(6)->get(),
            'ultimosEmpleados' => Empleado::with(['departamento', 'puesto'])->latest()->take(6)->get(),
        ]);
    }
}
