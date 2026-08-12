@extends('layouts.app')
@section('title','Reportes')
@section('header','Reportes')
@section('content')
<h2 class="h5 fw-bold mb-3">Financieros</h2>
<div class="row g-3 mb-4">
@foreach([
['Resumen por periodo','reportes.resumen-periodo','bi-calendar3'],
['Costo por departamento','reportes.costo-departamento','bi-diagram-3'],
['Comparativo mensual','reportes.comparativo','bi-graph-up'],
['Nominas','reportes.nominas','bi-cash-stack'],
['Pagos pendientes','reportes.pagos-pendientes','bi-hourglass-split']
] as [$label,$route,$icon])
<div class="col-md-4"><a href="{{ route($route) }}" class="soft-card quick-link p-4 d-flex align-items-center gap-3"><div class="stat-icon"><i class="bi {{ $icon }}"></i></div><strong>{{ $label }}</strong></a></div>
@endforeach
</div>
<h2 class="h5 fw-bold mb-3">Recursos humanos</h2>
<div class="row g-3">
@foreach([
['Empleados','reportes.empleados','bi-people'],['Asistencias','reportes.asistencias','bi-calendar-check'],['Permisos','reportes.permisos','bi-calendar2-week'],['Por departamento','reportes.departamentos','bi-building']
] as [$label,$route,$icon])
<div class="col-md-4"><a href="{{ route($route) }}" class="soft-card quick-link p-4 d-flex align-items-center gap-3"><div class="stat-icon"><i class="bi {{ $icon }}"></i></div><strong>{{ $label }}</strong></a></div>
@endforeach
</div>
@endsection
