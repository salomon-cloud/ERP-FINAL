@extends('layouts.app')

@section('title', 'Nuevo cobro')
@section('header', 'Nuevo cobro')

@section('content')
    <x-page-header title="Nuevo cobro" subtitle="Ventas / Cobros / Nuevo" />

    <x-card subtitle="El cobro nace en borrador; aplicarlo es lo que baja el saldo de la factura.">
        <form method="POST" action="{{ route('ventas.cobros.store') }}">
            @include('ventas::paginas.cobros._form')
        </form>
    </x-card>
@endsection
