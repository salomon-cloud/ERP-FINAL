@extends('layouts.app')

@section('title', 'Editar regla de reorden')
@section('header', 'Editar regla de reorden')

@section('content')
    <x-page-header :title="$regla->producto?->nombre ?? 'Regla'" subtitle="Inventario / Minimos y maximos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.reglas-reorden.update', $regla) }}">
            @method('PUT')
            @include('inventario::paginas.reglas-reorden._form')
        </form>
    </x-card>
@endsection
