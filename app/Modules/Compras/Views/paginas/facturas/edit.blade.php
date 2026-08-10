@extends('layouts.app')

@section('title', 'Editar factura de proveedor')
@section('header', 'Editar factura de proveedor')

@section('content')
    <x-page-header :title="$factura->numero_factura" subtitle="Compras / Facturas / Editar" />

    <x-card>
        <form method="POST" action="{{ route('compras.facturas.update', $factura) }}">
            @method('PUT')
            @include('compras::paginas.facturas._form')
        </form>
    </x-card>
@endsection
