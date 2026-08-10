@extends('layouts.app')

@section('title', 'Editar requisicion')
@section('header', 'Editar requisicion')

@section('content')
    <x-page-header :title="$requisicion->numero_requisicion" subtitle="Compras / Requisiciones / Editar" />

    <x-card>
        <form method="POST" action="{{ route('compras.requisiciones.update', $requisicion) }}">
            @method('PUT')
            @include('compras::paginas.requisiciones._form')
        </form>
    </x-card>
@endsection
