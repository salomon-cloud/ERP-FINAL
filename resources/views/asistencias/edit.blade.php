@extends('layouts.app')
@section('header','Editar asistencia')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('asistencias.update',$asistencia) }}">@method('PUT')@include('asistencias._form')</form></div>@endsection
