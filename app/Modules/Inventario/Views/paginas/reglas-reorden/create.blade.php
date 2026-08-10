@extends('layouts.app')

@section('title', 'Nueva regla de reorden')
@section('header', 'Nueva regla de reorden')

@section('content')
    <x-page-header title="Nueva regla de reorden" subtitle="Inventario / Minimos y maximos / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('inventario.reglas-reorden.store') }}">
            @include('inventario::paginas.reglas-reorden._form')
        </form>
    </x-card>
@endsection
