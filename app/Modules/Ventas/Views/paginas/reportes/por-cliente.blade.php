@extends('layouts.app')

@section('title', 'Ventas por cliente')
@section('header', 'Ventas por cliente')

@section('content')
    <x-page-header title="Ventas por cliente" subtitle="Ventas / Reportes / Por cliente">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'ventas.reportes.por-cliente'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica" />

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} cliente(s)</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Total vendido</div>
                <div class="fs-4 fw-bold">${{ number_format($total, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Codigo', 'Cliente', ['label' => 'Facturas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], ['label' => '% del total', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="text-muted">{{ $fila->codigo }}</td>
                    <td class="fw-bold">{{ $fila->nombre }}</td>
                    <td class="text-end">{{ $fila->facturas }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $fila->total, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $fila->saldo, 2) }}</td>
                    <td class="text-end text-muted">
                        {{ $total > 0 ? number_format((float) $fila->total / $total * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay ventas en ese periodo." icon="people" />
            @endforelse
        </x-table>
    </x-card>
@endsection
