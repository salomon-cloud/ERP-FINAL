@extends('layouts.app')

@section('title', 'Nuevo contrato')
@section('header', 'Nuevo contrato')

@section('content')
    <x-page-header title="Nuevo contrato" subtitle="RH / Contratos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.contratos.store') }}">
            @include('rh::paginas.contratos._form')
        </form>
    </x-card>
@endsection
