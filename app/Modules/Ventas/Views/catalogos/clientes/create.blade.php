@extends('layouts.app')

@section('title', 'Nuevo cliente')
@section('header', 'Nuevo cliente')

@section('content')
    <x-page-header title="Nuevo cliente" subtitle="Ventas / Clientes / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('ventas.clientes.store') }}">
            @include('ventas::catalogos.clientes._form')
        </form>
    </x-card>
@endsection
