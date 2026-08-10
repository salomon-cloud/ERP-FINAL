@extends('layouts.app')

@section('title', 'Nueva orden de compra')
@section('header', 'Nueva orden de compra')

@section('content')
    <x-page-header title="Nueva orden de compra" subtitle="Compras / Ordenes / Nueva" />

    <x-card subtitle="Primero el proveedor y las fechas; las lineas se agregan en la ficha.">
        <form method="POST" action="{{ route('compras.ordenes.store') }}">
            @include('compras::paginas.ordenes._form')
        </form>
    </x-card>
@endsection
