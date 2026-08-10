@extends('layouts.app')

@section('title', 'Nueva categoria')
@section('header', 'Nueva categoria')

@section('content')
    <x-page-header title="Nueva categoria" subtitle="Inventario / Categorias / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('inventario.categorias.store') }}">
            @include('inventario::catalogos.categorias._form')
        </form>
    </x-card>
@endsection
