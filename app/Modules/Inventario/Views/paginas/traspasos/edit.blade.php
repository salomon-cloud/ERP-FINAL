@extends('layouts.app')

@section('title', 'Editar traspaso')
@section('header', 'Editar traspaso')

@section('content')
    <x-page-header :title="$traspaso->numero_traspaso" subtitle="Inventario / Traspasos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('inventario.traspasos.update', $traspaso) }}">
            @method('PUT')
            @include('inventario::paginas.traspasos._form')
        </form>
    </x-card>
@endsection
