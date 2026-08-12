@extends('layouts.app')

@section('title', 'Contactos')
@section('header', 'Contactos')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Contactos" subtitle="CRM / Contactos" />

    <x-card>
        <x-table :head="['Nombre', 'Empresa', 'Correo', 'Telefono']">
            @forelse ($contactos as $contacto)
                <tr>
                    <td class="fw-bold">{{ $contacto->nombre.' '.$contacto->apellidos }}</td>
                    <td>{{ $contacto->empresa?->nombre ?: '--' }}</td>
                    <td>{{ $contacto->correo ?: '--' }}</td>
                    <td>{{ $contacto->telefono ?: '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="4" message="No hay contactos registrados." icon="people" />
            @endforelse
        </x-table>
        {{ $contactos->links() }}
    </x-card>
@endsection