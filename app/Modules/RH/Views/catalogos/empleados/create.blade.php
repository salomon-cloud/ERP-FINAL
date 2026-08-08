@extends('layouts.app')

@section('title', 'Nuevo empleado')
@section('header', 'Nuevo empleado')

@section('content')
    <x-page-header title="Nuevo empleado" subtitle="RH / Empleados / Nuevo" />

    <x-card>
        <form method="POST" action="{{ route('rh.empleados.store') }}" enctype="multipart/form-data">
            @include('rh::catalogos.empleados._form')
        </form>
    </x-card>
@endsection
