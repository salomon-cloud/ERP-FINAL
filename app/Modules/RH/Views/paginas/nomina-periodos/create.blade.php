@extends('layouts.app')

@section('title', 'Nuevo periodo')
@section('header', 'Nuevo periodo de nomina')

@section('content')
    <x-page-header title="Nuevo periodo" subtitle="RH / Nomina / Periodos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.nomina-periodos.store') }}">
            @include('rh::paginas.nomina-periodos._form')
        </form>
    </x-card>
@endsection
