@extends('layouts.app')

@section('title', 'Editar departamento')
@section('header', 'Editar departamento')

@section('content')
    <x-page-header :title="$departamento->nombre" subtitle="RH / Departamentos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.departamentos.update', $departamento) }}">
            @method('PUT')
            @include('rh::catalogos.departamentos._form')
        </form>
    </x-card>
@endsection
