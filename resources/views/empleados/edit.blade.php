@extends('layouts.app')
@section('title', 'Editar empleado')
@section('header', 'Editar empleado')
@section('content')
<div class="soft-card p-4"><form method="POST" action="{{ route('empleados.update', $empleado) }}" enctype="multipart/form-data">@method('PUT')@include('empleados._form')</form></div>
@endsection
