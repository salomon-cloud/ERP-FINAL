@extends('layouts.app')

@section('title', 'Editar pago')
@section('header', 'Editar pago')

@section('content')
    <x-page-header :title="$pago->numero_pago" subtitle="Compras / Pagos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('compras.pagos.update', $pago) }}">
            @method('PUT')
            @include('compras::paginas.pagos._form')
        </form>
    </x-card>
@endsection
