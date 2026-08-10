@extends('layouts.app')

@section('title', 'Nuevo pedido')
@section('header', 'Nuevo pedido')

@section('content')
    <x-page-header title="Nuevo pedido" subtitle="Ventas / Pedidos / Nuevo" />

    <x-card subtitle="Primero el cliente; las lineas y su almacen se agregan en la ficha.">
        <form method="POST" action="{{ route('ventas.pedidos.store') }}">
            @include('ventas::paginas.pedidos._form')
        </form>
    </x-card>
@endsection
