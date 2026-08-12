@extends('layouts.app')

@section('title', 'Prospectos')
@section('header', 'Prospectos')
@section('subtitle', 'CRM / Reportes')

@section('content')
    <x-page-header title="Prospectos" subtitle="CRM / Reportes" />

    <x-card>
        <x-table :head="['Nombre', 'Empresa', 'Estado']">
            @foreach ($prospectos as $prospecto)
                <tr>
                    <td class="fw-bold">{{ $prospecto->nombre.' '.$prospecto->apellidos }}</td>
                    <td>{{ $prospecto->empresa_nombre ?: '--' }}</td>
                    <td>{{ $prospecto->estado->label() }}</td>
                </tr>
            @endforeach
        </x-table>
    </x-card>
@endsection