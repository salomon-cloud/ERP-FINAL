@extends('layouts.app')

@section('title', 'Editar unidad')
@section('header', 'Editar unidad de medida')

@section('content')
    <x-page-header :title="$unidad->nombre" subtitle="Inventario / Unidades / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.unidades.update', $unidad) }}">
            @method('PUT')
            @include('inventario::catalogos.unidades._form')
        </form>
    </x-card>
@endsection
