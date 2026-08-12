@extends('layouts.app')

@section('title', 'Empresa')
@section('header', $empresa->nombre)
@section('subtitle', 'CRM / Empresas')

@section('content')
    <x-page-header :title="$empresa->nombre" subtitle="CRM / Empresas" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">RFC</dt><dd class="col-8">{{ $empresa->rfc ?: '--' }}</dd>
            <dt class="col-4">Giro</dt><dd class="col-8">{{ $empresa->giro ?: '--' }}</dd>
            <dt class="col-4">Correo</dt><dd class="col-8">{{ $empresa->correo ?: '--' }}</dd>
            <dt class="col-4">Telefono</dt><dd class="col-8">{{ $empresa->telefono ?: '--' }}</dd>
            <dt class="col-4">Direccion</dt><dd class="col-8">{{ $empresa->direccion ?: '--' }}</dd>
        </dl>
    </x-card>
@endsection