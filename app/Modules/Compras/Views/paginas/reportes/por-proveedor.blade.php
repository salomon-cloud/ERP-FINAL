@extends('layouts.app')

@section('title', 'Compras por proveedor')
@section('header', 'Compras por proveedor')

@section('content')
    <x-page-header title="Compras por proveedor" subtitle="Compras / Reportes / Por proveedor">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'compras.reportes.por-proveedor'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica" />

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} proveedor(es)</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Total comprado</div>
                <div class="fs-4 fw-bold">${{ number_format($total, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Codigo', 'Proveedor', ['label' => 'Facturas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], ['label' => '% del total', 'align' => 'end']]">
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
                <x-empty :colspan="6" message="No hay compras en ese periodo." icon="shop" />
            @endforelse
        </x-table>
    </x-card>
@endsection
