@extends('layouts.app')

@section('title', 'Editar asistencia')
@section('header', 'Editar asistencia')

@section('content')
    <x-page-header title="Editar asistencia" subtitle="RH / Asistencias / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.asistencias.update', $asistencia) }}">
            @method('PUT')
            @include('rh::paginas.asistencias._form')
        </form>
    </x-card>
@endsection
