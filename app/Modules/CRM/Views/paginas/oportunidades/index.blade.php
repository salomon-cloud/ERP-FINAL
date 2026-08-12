@extends('layouts.app')

@section('title', 'Oportunidades')
@section('header', 'Oportunidades')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Oportunidades" subtitle="CRM / Oportunidades" />

    <x-card>
        <x-table :head="['Nombre', 'Etapa', 'Monto', 'Responsable', '']">
            @forelse ($oportunidades as $oportunidad)
                <tr>
                    <td class="fw-bold">{{ $oportunidad->nombre }}</td>
                    <td>{{ $oportunidad->etapa->label() }}</td>
                    <td>${{ number_format((float) $oportunidad->monto, 2) }}</td>
                    <td>{{ $oportunidad->asignadoA?->name ?: '--' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-info" href="{{ route('crm.oportunidades.show', $oportunidad) }}"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay oportunidades registradas." icon="diagram-3" />
            @endforelse
        </x-table>
        {{ $oportunidades->links() }}
    </x-card>
@endsection