@extends('layouts.app')

@section('title', 'Embudo')
@section('header', 'Embudo')
@section('subtitle', 'CRM / Reportes')

@section('content')
    <x-page-header title="Embudo" subtitle="CRM / Reportes" />

    <x-card>
        <x-table :head="['Etapa', 'Oportunidades', 'Monto']">
            @foreach ($embudo as $fila)
                <tr>
                    <td class="fw-bold">{{ $fila->etapa }}</td>
                    <td>{{ $fila->total }}</td>
                    <td>${{ number_format((float) $fila->monto, 2) }}</td>
                </tr>
            @endforeach
        </x-table>
    </x-card>
@endsection