@extends('layouts.app')

@section('title', 'Editar producto')
@section('header', 'Editar producto')

@section('content')
    <x-page-header :title="$producto->nombre" subtitle="Inventario / Productos / Editar">
        <a class="btn btn-outline-secondary" href="{{ route('inventario.productos.show', $producto) }}">Ver ficha</a>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('inventario.productos.update', $producto) }}">
            @method('PUT')
            @include('inventario::catalogos.productos._form')
        </form>
    </x-card>
@endsection
