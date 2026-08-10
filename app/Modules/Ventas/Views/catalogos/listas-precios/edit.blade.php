@extends('layouts.app')

@section('title', 'Editar lista de precios')
@section('header', 'Editar lista de precios')

@section('content')
    <x-page-header :title="$lista->nombre" subtitle="Ventas / Listas de precios / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.listas-precios.update', $lista) }}">
            @method('PUT')
            @include('ventas::catalogos.listas-precios._form')
        </form>
    </x-card>
@endsection
