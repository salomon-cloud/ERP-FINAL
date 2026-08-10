@extends('layouts.app')

@section('title', 'Devoluciones y notas de credito')
@section('header', 'Devoluciones y notas de credito')

@section('content')
    <x-page-header title="Devoluciones y notas de credito" subtitle="Ventas / Reportes / Devoluciones">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'ventas.reportes.devoluciones'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica" />

    <div class="row g-3 mb-3">
        @foreach ($porMotivo as $motivo => $resumen)
            <div class="col-6 col-lg-3">
                <x-stat-card :label="$motivo" :value="'$'.number_format($resumen->total, 2)" icon="arrow-return-left"
                    :hint="$resumen->notas.' nota(s)'" />
            </div>
        @endforeach
    </div>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} nota(s) emitida(s)</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Total acreditado</div>
                <div class="fs-4 fw-bold">${{ number_format($total, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Folio', 'Fecha', 'Cliente', 'Factura', 'Motivo', ['label' => 'Total', 'align' => 'end']]">
            @forelse ($filas as $nota)
                <tr>
                    <td class="fw-bold">{{ $nota->numero_nota }}</td>
                    <td class="small">{{ $nota->fecha_emision?->format('d/m/Y') }}</td>
                    <td>{{ $nota->cliente?->nombre }}</td>
                    <td class="small">{{ $nota->factura?->numero_factura }}</td>
                    <td class="small">{{ $nota->motivo->label() }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $nota->total, 2) }}</td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay notas de credito en ese periodo." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
