@extends('layouts.app')

@section('title', 'Nuevo proveedor')
@section('header', 'Nuevo proveedor')

@section('content')
    <x-page-header title="Nuevo proveedor" subtitle="Compras / Proveedores / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('compras.proveedores.store') }}">
            @include('compras::catalogos.proveedores._form')
        </form>
    </x-card>
@endsection
