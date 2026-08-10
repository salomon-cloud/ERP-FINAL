@extends('layouts.app')

@section('title', 'Nueva cotizacion')
@section('header', 'Nueva cotizacion')

@section('content')
    <x-page-header title="Nueva cotizacion" subtitle="Ventas / Cotizaciones / Nueva" />

    <x-card subtitle="Primero el cliente y la vigencia; las lineas se agregan en la ficha.">
        <form method="POST" action="{{ route('ventas.cotizaciones.store') }}">
            @include('ventas::paginas.cotizaciones._form')
        </form>
    </x-card>
@endsection
