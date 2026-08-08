@extends('layouts.app')

@section('title', 'Nuevo departamento')
@section('header', 'Nuevo departamento')

@section('content')
    <x-page-header title="Nuevo departamento" subtitle="RH / Departamentos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.departamentos.store') }}">
            @include('rh::catalogos.departamentos._form')
        </form>
    </x-card>
@endsection
