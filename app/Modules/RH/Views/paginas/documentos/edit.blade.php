@extends('layouts.app')

@section('title', 'Editar documento')
@section('header', 'Editar documento')

@section('content')
    <x-page-header :title="$documento->titulo" subtitle="RH / Documentos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.documentos.update', $documento) }}">
            @method('PUT')
            @include('rh::paginas.documentos._form')
        </form>
    </x-card>
@endsection
