@extends('layouts.app')

@section('title', 'Recibo de nomina')
@section('header', 'Recibo de nomina')

@section('content')
    <x-page-header :title="$nomina->empleado?->nombre_completo ?? 'Recibo'"
                   :subtitle="'RH / Nomina / Recibos / '.$nomina->periodo_pago">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </x-page-header>

    <x-card title="Recibo {{ $nomina->periodo_pago }}">
        <dl class="row mb-4">
            <dt class="col-sm-3">Empleado</dt><dd class="col-sm-9">{{ $nomina->empleado?->nombre_completo ?? '--' }}</dd>
            <dt class="col-sm-3">Corrida</dt><dd class="col-sm-9">{{ $nomina->corrida?->numero_corrida ?? 'Captura suelta' }}</dd>
            <dt class="col-sm-3">Fecha de pago</dt><dd class="col-sm-9">{{ $nomina->fecha_pago->format('d/m/Y') }}</dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$nomina->estado->color()" :label="$nomina->estado->label()" /></dd>
            @if ($nomina->pagada_en)
                <dt class="col-sm-3">Pagado el</dt><dd class="col-sm-9">{{ $nomina->pagada_en->format('d/m/Y H:i') }}</dd>
            @endif
        </dl>

        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase text-muted">Percepciones</h6>
                <x-table :head="['Concepto', ['label' => 'Importe', 'align' => 'end']]">
                    <tr><td>Sueldo base</td><td class="text-end">${{ number_format((float) $nomina->sueldo_base, 2) }}</td></tr>
                    <tr><td>Bonos</td><td class="text-end">${{ number_format((float) $nomina->bonos, 2) }}</td></tr>
                    <tr>
                        <td>Horas extra ({{ (float) $nomina->horas_extra_cantidad }} h)</td>
                        <td class="text-end">${{ number_format((float) $nomina->horas_extra, 2) }}</td>
                    </tr>
                </x-table>
            </div>

            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase text-muted">Deducciones</h6>
                <x-table :head="['Concepto', ['label' => 'Importe', 'align' => 'end']]">
                    <tr>
                        <td>Ausencias ({{ (float) $nomina->dias_ausencia }} dias)</td>
                        <td class="text-end">${{ number_format((float) $nomina->deducciones, 2) }}</td>
                    </tr>
                    <tr><td>ISR</td><td class="text-end">${{ number_format((float) $nomina->isr, 2) }}</td></tr>
                    <tr><td>IMSS</td><td class="text-end">${{ number_format((float) $nomina->imss, 2) }}</td></tr>
                </x-table>
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center gap-3 mt-3 pt-3 border-top">
            <span class="text-uppercase fw-bold text-muted">Neto a pagar</span>
            <span class="fs-3 fw-bold">${{ number_format((float) $nomina->total_pagar, 2) }}</span>
        </div>

        @if ($nomina->notas)
            <p class="text-muted small mt-3 mb-0">{{ $nomina->notas }}</p>
        @endif
    </x-card>
@endsection
