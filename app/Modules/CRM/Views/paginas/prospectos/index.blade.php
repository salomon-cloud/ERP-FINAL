@extends('layouts.app')

@section('title', 'Prospectos')
@section('header', 'Prospectos')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Prospectos" subtitle="CRM / Prospectos" />

    <x-card>
        <x-table :head="['Nombre', 'Empresa', 'Estado', 'Asignado a', '']">
            @forelse ($prospectos as $prospecto)
                <tr>
                    <td class="fw-bold">{{ $prospecto->nombre.' '.$prospecto->apellidos }}</td>
                    <td>{{ $prospecto->empresa_nombre ?: '--' }}</td>
                    <td>{{ $prospecto->estado->label() }}</td>
                    <td>{{ $prospecto->asignadoA?->name ?: '--' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-info" href="{{ route('crm.prospectos.show', $prospecto) }}"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay prospectos registrados." icon="person-badge" />
            @endforelse
        </x-table>
        {{ $prospectos->links() }}
    </x-card>
@endsection