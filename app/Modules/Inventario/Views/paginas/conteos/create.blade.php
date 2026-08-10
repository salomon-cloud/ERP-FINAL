@extends('layouts.app')

@section('title', 'Nuevo conteo')
@section('header', 'Nuevo conteo fisico')

@section('content')
    <x-page-header title="Nuevo conteo fisico" subtitle="Inventario / Conteos / Nuevo" />

    <x-card subtitle="Al iniciarlo se congela la existencia esperada de cada producto.">
        <form method="POST" action="{{ route('inventario.conteos.store') }}">
            @include('inventario::paginas.conteos._form')
        </form>
    </x-card>
@endsection
