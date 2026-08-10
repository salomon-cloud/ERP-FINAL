@extends('layouts.app')

@section('title', 'Editar categoria')
@section('header', 'Editar categoria')

@section('content')
    <x-page-header :title="$categoria->nombre" subtitle="Inventario / Categorias / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.categorias.update', $categoria) }}">
            @method('PUT')
            @include('inventario::catalogos.categorias._form')
        </form>
    </x-card>
@endsection
