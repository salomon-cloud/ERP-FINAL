@extends('layouts.app')

@section('title', 'Editar empleado')
@section('header', 'Editar empleado')

@section('content')
    <x-page-header :title="$empleado->nombre_completo" subtitle="RH / Empleados / Editar" />

    <x-card>
        <form method="POST" action="{{ route('rh.empleados.update', $empleado) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('rh::catalogos.empleados._form')
        </form>
    </x-card>
@endsection
