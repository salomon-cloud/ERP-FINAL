@extends('layouts.app')

@section('title', 'Cuentas por pagar')
@section('header', 'Cuentas por pagar')

@section('content')
    <x-page-header title="Cuentas por pagar" subtitle="Compras / Reportes / Cuentas por pagar">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'compras.reportes.cuentas-por-pagar'])
    </x-page-header>

    <x-filter-bar placeholder="No aplica">
        <div class="col-md-3">
            <label class="form-label" for="filtro-proveedor">Proveedor</label>
            <select class="form-select" id="filtro-proveedor" name="proveedor_id">
                <option value="">Todos</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((int) request('proveedor_id') === $proveedor->id)>{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} factura(s) con saldo</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Saldo total</div>
                <div class="fs-4 fw-bold">${{ number_format($saldoTotal, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Folio', 'Proveedor', 'Fecha', 'Vencimiento', ['label' => 'Total', 'align' => 'end'], ['label' => 'Pagado', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Antiguedad', '']">
            @forelse ($filas as $factura)
                @php
                    $dias = $factura->fecha_vencimiento !== null
                        ? (int) $factura->fecha_vencimiento->diffInDays(now()->startOfDay(), false)
                        : null;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->proveedor?->nombre }}</td>
                    <td class="small">{{ $factura->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $factura->fecha_vencimiento?->format('d/m/Y') ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $factura->total_pagado, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($factura->saldo, 2) }}</td>
                    <td>
                        @if ($dias === null)
                            <span class="text-muted small">Sin vencimiento</span>
                        @elseif ($dias > 0)
                            <span class="badge-soft badge-cancelada">{{ $dias }} dias vencida</span>
                        @else
                            <span class="badge-soft badge-pendiente">Vence en {{ abs($dias) }} dias</span>
                        @endif
                    </td>
                    <td class="text-end no-print">
                        <a class="btn btn-sm btn-outline-primary action-btn"
                           href="{{ route('compras.pagos.create', ['factura_proveedor_id' => $factura->id]) }}"
                           title="Pagar"><i class="bi bi-cash-coin"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay nada pendiente de pago." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
