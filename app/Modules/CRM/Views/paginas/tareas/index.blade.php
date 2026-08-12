@extends('layouts.app')

@section('title', 'Tareas')
@section('header', 'Tareas')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Tareas" subtitle="CRM / Tareas" />

    <x-card>
        <x-table :head="['Titulo', 'Prioridad', 'Estado', 'Vence', 'Asignada a']">
            @forelse ($tareas as $tarea)
                <tr>
                    <td class="fw-bold">{{ $tarea->titulo }}</td>
                    <td>{{ $tarea->prioridad->label() }}</td>
                    <td>{{ $tarea->estado->label() }}</td>
                    <td>{{ $tarea->fecha_limite?->format('d/m/Y') ?: '--' }}</td>
                    <td>{{ $tarea->asignadaA?->name ?: '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay tareas registradas." icon="check2-square" />
            @endforelse
        </x-table>
        {{ $tareas->links() }}
    </x-card>
@endsection