@extends('layouts.app')

@section('title', 'Editar evaluacion')
@section('header', 'Editar evaluacion')

@section('content')
    <x-page-header title="Editar evaluacion" subtitle="RH / Evaluaciones / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.evaluaciones.update', $evaluacion) }}">
            @method('PUT')
            @include('rh::paginas.evaluaciones._form')
        </form>
    </x-card>
@endsection
