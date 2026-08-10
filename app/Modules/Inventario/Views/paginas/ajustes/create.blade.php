@extends('layouts.app')

@section('title', 'Nuevo ajuste')
@section('header', 'Nuevo ajuste de inventario')

@section('content')
    <x-page-header title="Nuevo ajuste" subtitle="Inventario / Ajustes / Nuevo" />

    <x-card subtitle="Primero el motivo; las lineas se agregan en la ficha del ajuste.">
        <form method="POST" action="{{ route('inventario.ajustes.store') }}">
            @include('inventario::paginas.ajustes._form')
        </form>
    </x-card>
@endsection
