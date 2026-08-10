@extends('layouts.app')

@section('title', 'Nuevo almacen')
@section('header', 'Nuevo almacen')

@section('content')
    <x-page-header title="Nuevo almacen" subtitle="Inventario / Almacenes / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('inventario.almacenes.store') }}">
            @include('inventario::catalogos.almacenes._form')
        </form>
    </x-card>
@endsection
