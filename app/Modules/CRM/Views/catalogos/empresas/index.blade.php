@extends('layouts.app')

@section('title', 'Empresas')
@section('header', 'Empresas')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Empresas" subtitle="CRM / Empresas" />

    <x-card>
        <x-table :head="['Nombre', 'RFC', 'Giro', 'Estado', '']">
            @forelse ($empresas as $empresa)
                <tr>
                    <td class="fw-bold">{{ $empresa->nombre }}</td>
                    <td>{{ $empresa->rfc ?: '--' }}</td>
                    <td>{{ $empresa->giro ?: '--' }}</td>
                    <td>{{ $empresa->estado }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-info" href="{{ route('crm.empresas.show', $empresa) }}"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay empresas registradas." icon="buildings" />
            @endforelse
        </x-table>
        {{ $empresas->links() }}
    </x-card>
@endsection