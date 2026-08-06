@extends('layouts.app')
@section('header','Editar usuario')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('usuarios.update',$usuario) }}">@method('PUT')@include('usuarios._form')</form></div>@endsection
