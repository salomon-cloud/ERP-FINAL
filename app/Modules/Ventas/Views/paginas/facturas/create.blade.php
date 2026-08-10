@extends('layouts.app')

@section('title', 'Nueva factura')
@section('header', 'Nueva factura')

@section('content')
    <x-page-header title="Nueva factura" subtitle="Ventas / Facturas / Nueva" />

    <x-card subtitle="Nace en borrador. Emitirla es lo que la contabiliza y la vuelve inmutable.">
        <form method="POST" action="{{ route('ventas.facturas.store') }}">
            @include('ventas::paginas.facturas._form')
        </form>
    </x-card>
@endsection
