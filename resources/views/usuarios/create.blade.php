@extends('layouts.app')
@section('header','Crear usuario')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('usuarios.store') }}">@include('usuarios._form')</form></div>@endsection
