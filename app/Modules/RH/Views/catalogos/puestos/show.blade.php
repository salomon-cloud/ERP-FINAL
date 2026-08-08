@extends('layouts.app')

@section('title', $puesto->nombre)
@section('header', $puesto->nombre)

@section('content')
    <x-page-header :title="$puesto->nombre" subtitle="RH / Puestos / Detalle">
        <a class="btn btn-outline-primary" href="{{ route('rh.puestos.edit', $puesto) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <x-card title="Datos del puesto">
        <dl class="row mb-0">
            <dt class="col-sm-3">Codigo</dt><dd class="col-sm-9">{{ $puesto->codigo ?? '--' }}</dd>
            <dt class="col-sm-3">Departamento</dt><dd class="col-sm-9">{{ $puesto->departamento?->nombre ?? '--' }}</dd>
            <dt class="col-sm-3">Sueldo minimo</dt><dd class="col-sm-9">${{ number_format((float) $puesto->sueldo_minimo, 2) }}</dd>
            <dt class="col-sm-3">Sueldo maximo</dt>
            <dd class="col-sm-9">{{ (float) $puesto->sueldo_maximo > 0 ? '$'.number_format((float) $puesto->sueldo_maximo, 2) : 'Sin tope' }}</dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$puesto->estado->color()" :label="$puesto->estado->label()" /></dd>
            <dt class="col-sm-3">Descripcion</dt><dd class="col-sm-9">{{ $puesto->descripcion ?? '--' }}</dd>
        </dl>
    </x-card>
@endsection
