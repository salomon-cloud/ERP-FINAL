@extends('layouts.app')

@section('title', 'Editar cliente')
@section('header', 'Editar cliente')

@section('content')
    <x-page-header :title="$cliente->nombre" subtitle="Ventas / Clientes / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.clientes.update', $cliente) }}">
            @method('PUT')
            @include('ventas::catalogos.clientes._form')
        </form>
    </x-card>
@endsection
