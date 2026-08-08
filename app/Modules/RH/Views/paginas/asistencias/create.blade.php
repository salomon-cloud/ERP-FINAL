@extends('layouts.app')

@section('title', 'Registrar asistencia')
@section('header', 'Registrar asistencia')

@section('content')
    <x-page-header title="Registrar asistencia" subtitle="RH / Asistencias / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('rh.asistencias.store') }}">
            @include('rh::paginas.asistencias._form')
        </form>
    </x-card>
@endsection
