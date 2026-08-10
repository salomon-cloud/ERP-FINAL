@extends('layouts.app')

@section('title', 'Nuevo traspaso')
@section('header', 'Nuevo traspaso')

@section('content')
    <x-page-header title="Nuevo traspaso" subtitle="Inventario / Traspasos / Nuevo" />

    <x-card subtitle="Primero los almacenes; las lineas se agregan en la ficha del traspaso.">
        <form method="POST" action="{{ route('inventario.traspasos.store') }}">
            @include('inventario::paginas.traspasos._form')
        </form>
    </x-card>
@endsection
