@extends('layouts.app')

@section('title', 'Nueva solicitud')
@section('header', 'Nueva solicitud')

@section('content')
    <x-page-header title="Nueva solicitud" subtitle="RH / Permisos / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('rh.permisos.store') }}">
            @include('rh::paginas.permisos._form')
        </form>
    </x-card>
@endsection
