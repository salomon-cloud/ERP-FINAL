@extends('layouts.app')
@section('header','Registrar asistencia')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('asistencias.store') }}">@include('asistencias._form')</form></div>@endsection
