@extends('layouts.app')

@section('title', 'Nuevo producto')
@section('header', 'Nuevo producto')

@section('content')
    <x-page-header title="Nuevo producto" subtitle="Inventario / Productos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('inventario.productos.store') }}">
            @include('inventario::catalogos.productos._form')
        </form>
    </x-card>
@endsection
