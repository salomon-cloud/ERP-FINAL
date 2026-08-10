@extends('layouts.app')

@section('title', 'Editar factura')
@section('header', 'Editar factura')

@section('content')
    <x-page-header :title="$factura->numero_factura" subtitle="Ventas / Facturas / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.facturas.update', $factura) }}">
            @method('PUT')
            @include('ventas::paginas.facturas._form')
        </form>
    </x-card>
@endsection
