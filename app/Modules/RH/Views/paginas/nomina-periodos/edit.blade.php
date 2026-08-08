@extends('layouts.app')

@section('title', 'Editar periodo')
@section('header', 'Editar periodo de nomina')

@section('content')
    <x-page-header :title="$periodo->codigo_periodo" subtitle="RH / Nomina / Periodos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.nomina-periodos.update', $periodo) }}">
            @method('PUT')
            @include('rh::paginas.nomina-periodos._form')
        </form>
    </x-card>
@endsection
