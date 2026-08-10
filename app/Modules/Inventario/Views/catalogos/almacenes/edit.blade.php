@extends('layouts.app')

@section('title', 'Editar almacen')
@section('header', 'Editar almacen')

@section('content')
    <x-page-header :title="$almacen->nombre" subtitle="Inventario / Almacenes / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.almacenes.update', $almacen) }}">
            @method('PUT')
            @include('inventario::catalogos.almacenes._form')
        </form>
    </x-card>
@endsection
