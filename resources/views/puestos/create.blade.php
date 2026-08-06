@extends('layouts.app')
@section('header','Nuevo puesto')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('puestos.store') }}">@include('puestos._form')</form></div>@endsection
