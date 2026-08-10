@extends('layouts.app')

@section('title', 'Editar orden de compra')
@section('header', 'Editar orden de compra')

@section('content')
    <x-page-header :title="$orden->numero_orden" subtitle="Compras / Ordenes / Editar" />

    <x-card>
        <form method="POST" action="{{ route('compras.ordenes.update', $orden) }}">
            @method('PUT')
            @include('compras::paginas.ordenes._form')
        </form>
    </x-card>
@endsection
