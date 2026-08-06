@extends('layouts.app')
@section('header','Editar nomina')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('nominas.update',$nomina) }}">@method('PUT')@include('nominas._form')</form></div>@endsection
