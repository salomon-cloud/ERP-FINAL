@extends('layouts.app')

@section('title', 'Nueva ubicacion')
@section('header', 'Nueva ubicacion')

@section('content')
    <x-page-header title="Nueva ubicacion" subtitle="Inventario / Ubicaciones / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('inventario.ubicaciones.store') }}">
            @include('inventario::catalogos.ubicaciones._form')
        </form>
    </x-card>
@endsection
