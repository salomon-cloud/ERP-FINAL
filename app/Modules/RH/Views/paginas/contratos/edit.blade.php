@extends('layouts.app')

@section('title', 'Editar contrato')
@section('header', 'Editar contrato')

@section('content')
    <x-page-header title="Editar contrato" subtitle="RH / Contratos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.contratos.update', $contrato) }}">
            @method('PUT')
            @include('rh::paginas.contratos._form')
        </form>
    </x-card>
@endsection
