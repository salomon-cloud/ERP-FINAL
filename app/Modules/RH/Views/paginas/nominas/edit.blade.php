@extends('layouts.app')

@section('title', 'Editar recibo')
@section('header', 'Editar recibo de nomina')

@section('content')
    <x-page-header title="Editar recibo" subtitle="RH / Nomina / Recibos / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.nominas.update', $nomina) }}">
            @method('PUT')
            @include('rh::paginas.nominas._form')
        </form>
    </x-card>
@endsection
