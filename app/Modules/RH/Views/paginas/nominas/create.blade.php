@extends('layouts.app')

@section('title', 'Nuevo recibo')
@section('header', 'Nuevo recibo de nomina')

@section('content')
    <x-page-header title="Nuevo recibo" subtitle="RH / Nomina / Recibos / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.nominas.store') }}">
            @include('rh::paginas.nominas._form')
        </form>
    </x-card>
@endsection
