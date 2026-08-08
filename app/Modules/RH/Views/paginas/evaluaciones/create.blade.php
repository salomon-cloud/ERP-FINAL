@extends('layouts.app')

@section('title', 'Nueva evaluacion')
@section('header', 'Nueva evaluacion')

@section('content')
    <x-page-header title="Nueva evaluacion" subtitle="RH / Evaluaciones / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('rh.evaluaciones.store') }}">
            @include('rh::paginas.evaluaciones._form')
        </form>
    </x-card>
@endsection
