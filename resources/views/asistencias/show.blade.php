@extends('layouts.app')
@section('header','Detalle de asistencia')
@section('content')<div class="soft-card p-4"><h2 class="h4 fw-bold">{{ $asistencia->empleado->nombre_completo }}</h2><p><strong>Fecha:</strong> {{ $asistencia->fecha->format('d/m/Y') }}</p><p><strong>Entrada:</strong> {{ $asistencia->hora_entrada }} <strong class="ms-3">Salida:</strong> {{ $asistencia->hora_salida }}</p>@include('partials.badge',['estado'=>$asistencia->estado])</div>@endsection
