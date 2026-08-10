@extends('layouts.app')

@section('title', 'Editar proveedor')
@section('header', 'Editar proveedor')

@section('content')
    <x-page-header :title="$proveedor->nombre" subtitle="Compras / Proveedores / Editar" />

    <x-card>
        <form method="POST" action="{{ route('compras.proveedores.update', $proveedor) }}">
            @method('PUT')
            @include('compras::catalogos.proveedores._form')
        </form>
    </x-card>
@endsection
