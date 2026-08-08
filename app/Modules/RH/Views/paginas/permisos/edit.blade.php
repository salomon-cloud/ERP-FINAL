@extends('layouts.app')

@section('title', 'Editar solicitud')
@section('header', 'Editar solicitud')

@section('content')
    <x-page-header title="Editar solicitud" subtitle="RH / Permisos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.permisos.update', $permiso) }}">
            @method('PUT')
            @include('rh::paginas.permisos._form')
        </form>
    </x-card>
@endsection
