@extends('layouts.app')

@section('title', 'Nueva factura de proveedor')
@section('header', 'Nueva factura de proveedor')

@section('content')
    <x-page-header title="Nueva factura de proveedor" subtitle="Compras / Facturas / Nueva" />

    <x-card subtitle="Con una orden ligada podras copiar de golpe lo que ya se recibio.">
        <form method="POST" action="{{ route('compras.facturas.store') }}">
            @include('compras::paginas.facturas._form')
        </form>
    </x-card>
@endsection
