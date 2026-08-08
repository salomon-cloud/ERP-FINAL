@extends('layouts.app')

@section('title', 'Nuevo puesto')
@section('header', 'Nuevo puesto')

@section('content')
    <x-page-header title="Nuevo puesto" subtitle="RH / Puestos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.puestos.store') }}">
            @include('rh::catalogos.puestos._form')
        </form>
    </x-card>
@endsection
