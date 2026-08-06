@extends('layouts.app')
@section('header','Editar puesto')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('puestos.update',$puesto) }}">@method('PUT')@include('puestos._form')</form></div>@endsection
