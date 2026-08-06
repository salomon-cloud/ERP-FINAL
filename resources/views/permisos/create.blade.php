@extends('layouts.app')
@section('header','Nueva solicitud')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('permisos.store') }}">@include('permisos._form')</form></div>@endsection
