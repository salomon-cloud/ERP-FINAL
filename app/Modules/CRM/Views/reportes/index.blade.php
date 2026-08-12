@extends('layouts.app')

@section('title', 'Reportes CRM')
@section('header', 'Reportes CRM')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Reportes CRM" subtitle="Embudo, prospectos y actividades">
        <a class="btn btn-outline-primary" href="{{ route('crm.reportes.embudo') }}">Embudo</a>
        <a class="btn btn-outline-primary" href="{{ route('crm.reportes.prospectos') }}">Prospectos</a>
        <a class="btn btn-outline-primary" href="{{ route('crm.reportes.actividades') }}">Actividades</a>
    </x-page-header>

    <x-card>
        <p class="mb-0">Selecciona un reporte de CRM.</p>
    </x-card>
@endsection