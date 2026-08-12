@extends('layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('content')
<div class="row g-3 mb-4">
    @php
        $stats = [
            ['Total empleados', $totalEmpleados, 'bi-people'],
            ['Activos', $empleadosActivos, 'bi-person-check'],
            ['Inactivos', $empleadosInactivos, 'bi-person-x'],
            ['Departamentos', $totalDepartamentos, 'bi-building'],
            ['Nominas generadas', $nominasGeneradas, 'bi-receipt'],
            ['Nominas pendientes', $nominasPendientes, 'bi-hourglass-split'],
            ['Pagos realizados', '$'.number_format($pagosRealizados, 2), 'bi-cash-coin'],
            ['Solicitudes pendientes', $solicitudesPendientes, 'bi-inbox'],
        ];
    @endphp
    @foreach($stats as [$label, $value, $icon])
        <div class="col-sm-6 col-xl-3">
            <div class="soft-card stat-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h3 fw-bold mt-2">{{ $value }}</div>
                </div>
                <div class="stat-icon"><i class="bi {{ $icon }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex flex-wrap gap-2 mb-4 dashboard-shortcuts">
    @php
        $quickLinks = auth()->user()->role === 'Empleado'
            ? [['Mi informacion', 'empleados.index', 'bi-person'], ['Mis nominas', 'nominas.index', 'bi-cash-stack'], ['Mis asistencias', 'asistencias.index', 'bi-calendar-check'], ['Mis permisos', 'permisos.index', 'bi-calendar2-week']]
            : [['Empleados', 'empleados.index', 'bi-person-plus'], ['Generar nomina', 'nominas.create', 'bi-cash-stack'], ['Registrar asistencia', 'asistencias.create', 'bi-calendar-plus'], ['Solicitudes', 'permisos.index', 'bi-calendar2-week']];
    @endphp
    @foreach($quickLinks as [$label, $route, $icon])
        @if(Route::has($route))
            <a href="{{ route($route) }}" class="btn btn-outline-primary btn-sm dashboard-shortcut">
                <i class="bi {{ $icon }} me-1"></i>{{ $label }}
            </a>
        @endif
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="soft-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Ultimas nominas</h2>
                <a href="{{ route('nominas.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Empleado</th><th>Periodo</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse($ultimasNominas as $nomina)
                        <tr><td>{{ $nomina->empleado->nombre_completo }}</td><td>{{ $nomina->periodo_pago }}</td><td>${{ number_format($nomina->total_pagar, 2) }}</td><td>@include('partials.badge', ['estado' => $nomina->estado])</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">Sin nominas registradas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="soft-card p-3">
            <h2 class="h5 fw-bold mb-3">Ultimos empleados</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Empleado</th><th>Departamento</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse($ultimosEmpleados as $empleado)
                        <tr><td>{{ $empleado->nombre_completo }}</td><td>{{ $empleado->departamento->nombre }}</td><td>@include('partials.badge', ['estado' => $empleado->estado])</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">Sin empleados registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
