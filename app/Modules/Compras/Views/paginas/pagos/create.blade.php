@extends('layouts.app')

@section('title', 'Nuevo pago')
@section('header', 'Nuevo pago a proveedor')

@section('content')
    <x-page-header title="Nuevo pago a proveedor" subtitle="Compras / Pagos / Nuevo" />

    <x-card subtitle="El pago nace en borrador; aplicarlo es lo que baja el saldo de la factura.">
        <form method="POST" action="{{ route('compras.pagos.store') }}">
            @include('compras::paginas.pagos._form')
        </form>
    </x-card>
@endsection
