@extends('layouts.app')

@section('title', 'Editar cotizacion')
@section('header', 'Editar cotizacion')

@section('content')
    <x-page-header :title="$cotizacion->numero_cotizacion" subtitle="Ventas / Cotizaciones / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.cotizaciones.update', $cotizacion) }}">
            @method('PUT')
            @include('ventas::paginas.cotizaciones._form')
        </form>
    </x-card>
@endsection
