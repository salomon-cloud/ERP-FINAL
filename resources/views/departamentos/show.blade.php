@extends('layouts.app')
@section('header',$departamento->nombre)
@section('content')
<div class="soft-card p-4 mb-4"><div class="d-flex justify-content-between"><div><h2 class="h4 fw-bold">{{ $departamento->nombre }}</h2><p class="text-muted mb-0">{{ $departamento->descripcion }}</p></div>@include('partials.badge',['estado'=>$departamento->estado])</div><hr><strong>Responsable:</strong> {{ $departamento->responsable }}</div>
<div class="row g-4"><div class="col-lg-6"><div class="soft-card p-3"><h3 class="h5 fw-bold">Empleados</h3><ul class="list-group list-group-flush">@forelse($departamento->empleados as $empleado)<li class="list-group-item">{{ $empleado->nombre_completo }}</li>@empty<li class="list-group-item text-muted">Sin empleados.</li>@endforelse</ul></div></div><div class="col-lg-6"><div class="soft-card p-3"><h3 class="h5 fw-bold">Puestos</h3><ul class="list-group list-group-flush">@forelse($departamento->puestos as $puesto)<li class="list-group-item">{{ $puesto->nombre }}</li>@empty<li class="list-group-item text-muted">Sin puestos.</li>@endforelse</ul></div></div></div>
@endsection
