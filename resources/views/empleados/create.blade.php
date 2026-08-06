@extends('layouts.app')
@section('title', 'Nuevo empleado')
@section('header', 'Registrar empleado')
@section('content')
<div class="soft-card p-4"><form method="POST" action="{{ route('empleados.store') }}" enctype="multipart/form-data">@include('empleados._form')</form></div>
@endsection
