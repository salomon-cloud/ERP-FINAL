@extends('layouts.app')

@section('title', 'Contacto')
@section('header', $contacto->nombre.' '.$contacto->apellidos)
@section('subtitle', 'CRM / Contactos')

@section('content')
    <x-page-header :title="$contacto->nombre.' '.$contacto->apellidos" subtitle="CRM / Contactos" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">Empresa</dt><dd class="col-8">{{ $contacto->empresa?->nombre ?: '--' }}</dd>
            <dt class="col-4">Correo</dt><dd class="col-8">{{ $contacto->correo ?: '--' }}</dd>
            <dt class="col-4">Telefono</dt><dd class="col-8">{{ $contacto->telefono ?: '--' }}</dd>
            <dt class="col-4">Puesto</dt><dd class="col-8">{{ $contacto->puesto ?: '--' }}</dd>
        </dl>
    </x-card>
@endsection