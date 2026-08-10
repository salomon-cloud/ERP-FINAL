@extends('layouts.app')

@section('title', 'Editar cobro')
@section('header', 'Editar cobro')

@section('content')
    <x-page-header :title="$cobro->numero_cobro" subtitle="Ventas / Cobros / Editar" />

    <x-card>
        <form method="POST" action="{{ route('ventas.cobros.update', $cobro) }}">
            @method('PUT')
            @include('ventas::paginas.cobros._form')
        </form>
    </x-card>
@endsection
