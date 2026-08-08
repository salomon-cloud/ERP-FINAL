@extends('layouts.app')

@section('title', 'Editar puesto')
@section('header', 'Editar puesto')

@section('content')
    <x-page-header :title="$puesto->nombre" subtitle="RH / Puestos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.puestos.update', $puesto) }}">
            @method('PUT')
            @include('rh::catalogos.puestos._form')
        </form>
    </x-card>
@endsection
