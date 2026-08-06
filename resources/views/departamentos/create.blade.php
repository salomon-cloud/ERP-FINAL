@extends('layouts.app')
@section('header','Nuevo departamento')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('departamentos.store') }}">@include('departamentos._form')</form></div>@endsection
