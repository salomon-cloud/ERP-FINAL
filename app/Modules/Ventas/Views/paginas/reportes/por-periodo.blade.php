@extends('layouts.app')

@section('title', 'Ventas por periodo')
@section('header', 'Ventas por periodo')

@section('content')
    <x-page-header title="Ventas por periodo" subtitle="Ventas / Reportes / Por periodo">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'ventas.reportes.por-periodo'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica" />

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} factura(s)</span>
            <div class="d-flex gap-4 text-end">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Facturado</div>
                    <div class="fs-4 fw-bold">${{ number_format($total, 2) }}</div>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Cobrado</div>
                    <div class="fs-4 fw-bold">${{ number_format($cobrado, 2) }}</div>
                </div>
            </div>
        </div>

        <x-table :head="['Folio', 'Fecha', 'Cliente', ['label' => 'Subtotal', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], ['label' => 'Cobrado', 'align' => 'end'], 'Estado']">
            @forelse ($filas as $factura)
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td class="small">{{ $factura->fecha_emision?->format('d/m/Y') }}</td>
                    <td>{{ $factura->cliente?->nombre }}</td>
                    <td class="text-end">${{ number_format((float) $factura->subtotal, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $factura->total_impuesto, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $factura->total_cobrado, 2) }}</td>
                    <td><x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" /></td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay ventas en ese periodo." icon="calendar-range" />
            @endforelse
        </x-table>
    </x-card>
@endsection
