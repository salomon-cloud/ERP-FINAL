@extends('layouts.app')

@section('title', 'Nuevo documento')
@section('header', 'Nuevo documento')

@section('content')
    <x-page-header title="Nuevo documento" subtitle="RH / Documentos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.documentos.store') }}">
            @include('rh::paginas.documentos._form')
        </form>
    </x-card>
@endsection
