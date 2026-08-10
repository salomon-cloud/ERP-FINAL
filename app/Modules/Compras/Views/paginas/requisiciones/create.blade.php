@extends('layouts.app')

@section('title', 'Nueva requisicion')
@section('header', 'Nueva requisicion')

@section('content')
    <x-page-header title="Nueva requisicion" subtitle="Compras / Requisiciones / Nueva" />

    <x-card subtitle="Primero la cabecera; lo que se necesita se agrega en la ficha.">
        <form method="POST" action="{{ route('compras.requisiciones.store') }}">
            @include('compras::paginas.requisiciones._form')
        </form>
    </x-card>
@endsection
