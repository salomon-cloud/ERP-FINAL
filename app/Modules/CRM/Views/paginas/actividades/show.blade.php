@extends('layouts.app')

@section('title', 'Actividad')
@section('header', 'Actividad')
@section('subtitle', 'CRM / Actividades')

@section('content')
    <x-page-header title="Actividad" subtitle="CRM / Actividades" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">Tipo</dt><dd class="col-8">{{ $actividad->tipo_actividad->label() }}</dd>
            <dt class="col-4">Resumen</dt><dd class="col-8">{{ $actividad->resumen }}</dd>
            <dt class="col-4">Resultado</dt><dd class="col-8">{{ $actividad->resultado ?: '--' }}</dd>
        </dl>
    </x-card>
@endsection