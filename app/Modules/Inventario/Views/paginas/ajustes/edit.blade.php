@extends('layouts.app')

@section('title', 'Editar ajuste')
@section('header', 'Editar ajuste')

@section('content')
    <x-page-header :title="$ajuste->numero_ajuste" subtitle="Inventario / Ajustes / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.ajustes.update', $ajuste) }}">
            @method('PUT')
            @include('inventario::paginas.ajustes._form')
        </form>
    </x-card>
@endsection
