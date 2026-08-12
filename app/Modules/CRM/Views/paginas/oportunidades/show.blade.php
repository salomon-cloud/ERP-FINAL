@extends('layouts.app')

@section('title', 'Oportunidad')
@section('header', $oportunidad->nombre)
@section('subtitle', 'CRM / Oportunidades')

@section('content')
    <x-page-header :title="$oportunidad->nombre" subtitle="CRM / Oportunidades" />

    <x-card>
        <dl class="row mb-0">
            <dt class="col-4">Etapa</dt><dd class="col-8">{{ $oportunidad->etapa->label() }}</dd>
            <dt class="col-4">Monto</dt><dd class="col-8">${{ number_format((float) $oportunidad->monto, 2) }}</dd>
            <dt class="col-4">Probabilidad</dt><dd class="col-8">{{ number_format((float) $oportunidad->probabilidad, 2) }}%</dd>
            <dt class="col-4">Descripcion</dt><dd class="col-8">{{ $oportunidad->descripcion ?: '--' }}</dd>
        </dl>
    </x-card>
@endsection