@extends('layouts.app')
@section('header','Editar departamento')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('departamentos.update',$departamento) }}">@method('PUT')@include('departamentos._form')</form></div>@endsection
