@extends('layouts.app')

@section('title', 'Actividades')
@section('header', 'Actividades')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Actividades" subtitle="CRM / Actividades" />

    <x-card>
        <x-table :head="['Tipo', 'Resumen', 'Programada', 'Asignada a']">
            @forelse ($actividades as $actividad)
                <tr>
                    <td class="fw-bold">{{ $actividad->tipo_actividad->label() }}</td>
                    <td>{{ $actividad->resumen }}</td>
                    <td>{{ optional($actividad->programada_en)->format('d/m/Y H:i') ?: '--' }}</td>
                    <td>{{ $actividad->asignadaA?->name ?: '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="4" message="No hay actividades registradas." icon="calendar-check" />
            @endforelse
        </x-table>
        {{ $actividades->links() }}
    </x-card>
@endsection