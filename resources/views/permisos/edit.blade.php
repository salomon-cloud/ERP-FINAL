@extends('layouts.app')
@section('header','Editar solicitud')
@section('content')<div class="soft-card p-4"><form method="POST" action="{{ route('permisos.update',$permiso) }}">@method('PUT')@include('permisos._form')</form></div>@endsection
