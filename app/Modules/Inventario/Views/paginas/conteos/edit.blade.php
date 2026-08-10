@extends('layouts.app')

@section('title', 'Editar conteo')
@section('header', 'Editar conteo')

@section('content')
    <x-page-header :title="$conteo->numero_conteo" subtitle="Inventario / Conteos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.conteos.update', $conteo) }}">
            @method('PUT')
            @include('inventario::paginas.conteos._form')
        </form>
    </x-card>
@endsection
