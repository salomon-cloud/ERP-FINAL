@extends('layouts.app')

@section('title', 'Tarea')
@section('header', $tarea->titulo)
@section('subtitle', 'CRM / Tareas')

@section('content')
    <x-page-header :title="$tarea->titulo" subtitle="CRM / Tareas" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">Descripcion</dt><dd class="col-8">{{ $tarea->descripcion ?: '--' }}</dd>
            <dt class="col-4">Prioridad</dt><dd class="col-8">{{ $tarea->prioridad->label() }}</dd>
            <dt class="col-4">Estado</dt><dd class="col-8">{{ $tarea->estado->label() }}</dd>
        </dl>
    </x-card>
@endsection