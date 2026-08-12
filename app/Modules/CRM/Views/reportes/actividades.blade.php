@extends('layouts.app')

@section('title', 'Actividades')
@section('header', 'Actividades')
@section('subtitle', 'CRM / Reportes')

@section('content')
    <x-page-header title="Actividades" subtitle="CRM / Reportes" />

    <x-card>
        <x-table :head="['Tipo', 'Resumen', 'Programada']">
            @foreach ($actividades as $actividad)
                <tr>
                    <td class="fw-bold">{{ $actividad->tipo_actividad->label() }}</td>
                    <td>{{ $actividad->resumen }}</td>
                    <td>{{ optional($actividad->programada_en)->format('d/m/Y H:i') ?: '--' }}</td>
                </tr>
            @endforeach
        </x-table>
    </x-card>
@endsection