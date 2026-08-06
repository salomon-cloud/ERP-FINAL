@extends('layouts.app')
@section('header','Generar nomina')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('nominas.store') }}">@include('nominas._form')</form></div>@endsection
