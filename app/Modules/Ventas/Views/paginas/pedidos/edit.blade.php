@extends('layouts.app')

@section('title', 'Editar pedido')
@section('header', 'Editar pedido')

@section('content')
    <x-page-header :title="$pedido->numero_pedido" subtitle="Ventas / Pedidos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.pedidos.update', $pedido) }}">
            @method('PUT')
            @include('ventas::paginas.pedidos._form')
        </form>
    </x-card>
@endsection
