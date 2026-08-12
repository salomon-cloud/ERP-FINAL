@extends('layouts.app')

@section('title', 'Prospecto')
@section('header', trim($prospecto->nombre.' '.$prospecto->apellidos))
@section('subtitle', 'CRM / Prospectos')

@section('content')
    <x-page-header :title="trim($prospecto->nombre.' '.$prospecto->apellidos)" subtitle="CRM / Prospectos" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">Empresa</dt><dd class="col-8">{{ $prospecto->empresa_nombre ?: '--' }}</dd>
            <dt class="col-4">Correo</dt><dd class="col-8">{{ $prospecto->correo ?: '--' }}</dd>
            <dt class="col-4">Telefono</dt><dd class="col-8">{{ $prospecto->telefono ?: '--' }}</dd>
            <dt class="col-4">Estado</dt><dd class="col-8">{{ $prospecto->estado->label() }}</dd>
            <dt class="col-4">Origen</dt><dd class="col-8">{{ $prospecto->origen->label() }}</dd>
            <dt class="col-4">Valor estimado</dt><dd class="col-8">${{ number_format((float) $prospecto->valor_estimado, 2) }}</dd>
            <dt class="col-4">Notas</dt><dd class="col-8">{{ $prospecto->notas ?: '--' }}</dd>
        </dl>
    </x-card>
@endsection