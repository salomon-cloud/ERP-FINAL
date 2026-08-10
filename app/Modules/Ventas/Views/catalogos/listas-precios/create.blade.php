@extends('layouts.app')

@section('title', 'Nueva lista de precios')
@section('header', 'Nueva lista de precios')

@section('content')
    <x-page-header title="Nueva lista de precios" subtitle="Ventas / Listas de precios / Nueva" />

    <x-card subtitle="Primero la cabecera; los precios se agregan en la ficha de la lista.">
        <form method="POST" action="{{ route('ventas.listas-precios.store') }}">
            @include('ventas::catalogos.listas-precios._form')
        </form>
    </x-card>
@endsection
