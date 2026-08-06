@extends('layouts.app')
@section('title', 'Detalle empleado')
@section('header', $empleado->nombre_completo)
@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="soft-card p-4 text-center">
            <div class="stat-icon mx-auto mb-3" style="width:90px;height:90px;font-size:2rem"><i class="bi bi-person"></i></div>
            <h2 class="h4 fw-bold">{{ $empleado->nombre_completo }}</h2>
            <p class="text-muted">{{ $empleado->puesto->nombre }} / {{ $empleado->departamento->nombre }}</p>
            @include('partials.badge', ['estado' => $empleado->estado])
        </div>
    </div>
    <div class="col-lg-8">
        <div class="soft-card p-4">
            <h3 class="h5 fw-bold mb-3">Informacion laboral</h3>
            <div class="row g-3">
                <div class="col-md-6"><strong>CURP:</strong> {{ $empleado->curp }}</div>
                <div class="col-md-6"><strong>RFC:</strong> {{ $empleado->rfc }}</div>
                <div class="col-md-6"><strong>Correo:</strong> {{ $empleado->correo }}</div>
                <div class="col-md-6"><strong>Telefono:</strong> {{ $empleado->telefono }}</div>
                <div class="col-md-6"><strong>Contratacion:</strong> {{ $empleado->fecha_contratacion->format('d/m/Y') }}</div>
                <div class="col-md-6"><strong>Sueldo base:</strong> ${{ number_format($empleado->sueldo_base, 2) }}</div>
                <div class="col-12"><strong>Direccion:</strong> {{ $empleado->direccion }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
