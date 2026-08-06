@extends('layouts.app')
@section('title','Reportes')
@section('header','Reportes')
@section('content')
<div class="row g-3">
@foreach([
['Empleados','reportes.empleados','bi-people'],['Nominas','reportes.nominas','bi-cash-stack'],['Asistencias','reportes.asistencias','bi-calendar-check'],['Permisos','reportes.permisos','bi-calendar2-week'],['Por departamento','reportes.departamentos','bi-building'],['Pagos pendientes','reportes.pagos-pendientes','bi-hourglass-split']
] as [$label,$route,$icon])
<div class="col-md-4"><a href="{{ route($route) }}" class="soft-card quick-link p-4 d-flex align-items-center gap-3"><div class="stat-icon"><i class="bi {{ $icon }}"></i></div><strong>{{ $label }}</strong></a></div>
@endforeach
</div>
@endsection
