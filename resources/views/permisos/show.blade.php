@extends('layouts.app')
@section('header','Detalle de solicitud')
@section('content')<div class="soft-card p-4"><div class="d-flex justify-content-between"><div><h2 class="h4 fw-bold">{{ $permiso->empleado->nombre_completo }}</h2><p class="text-muted">{{ ucfirst($permiso->tipo) }} del {{ $permiso->fecha_inicio->format('d/m/Y') }} al {{ $permiso->fecha_fin->format('d/m/Y') }}</p></div>@include('partials.badge',['estado'=>$permiso->estado])</div><hr><p>{{ $permiso->motivo }}</p></div>@endsection
