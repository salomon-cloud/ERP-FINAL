@extends('layouts.app')

@section('title', 'Nueva unidad')
@section('header', 'Nueva unidad de medida')

@section('content')
    <x-page-header title="Nueva unidad de medida" subtitle="Inventario / Unidades / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('inventario.unidades.store') }}">
            @include('inventario::catalogos.unidades._form')
        </form>
    </x-card>
@endsection
