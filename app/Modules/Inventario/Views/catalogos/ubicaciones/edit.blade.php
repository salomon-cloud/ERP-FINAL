@extends('layouts.app')

@section('title', 'Editar ubicacion')
@section('header', 'Editar ubicacion')

@section('content')
    <x-page-header :title="$ubicacion->nombre" subtitle="Inventario / Ubicaciones / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.ubicaciones.update', $ubicacion) }}">
            @method('PUT')
            @include('inventario::catalogos.ubicaciones._form')
        </form>
    </x-card>
@endsection
