@extends('layouts.app')
@section('title','Recibo de nomina')
@section('header','Recibo de nomina')
@section('content')
<div class="soft-card p-4">
    <div class="recibo">
        <div class="d-flex justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
            <div>
                <div class="fw-bold fs-5">{{ config('sistema.razon_social') }}</div>
                <div class="text-muted small">RFC: {{ config('sistema.rfc') }}</div>
                <div class="text-muted small">{{ config('sistema.direccion') }}</div>
                <div class="text-muted small"><i class="bi bi-telephone"></i> {{ config('sistema.telefono') }} &nbsp;<i class="bi bi-envelope"></i> {{ config('sistema.correo') }}</div>
            </div>
            <div class="text-end">
                <div class="fw-bold">Recibo de nomina</div>
                <div class="text-muted small">Folio: {{ $nomina->folio }}</div>
                <div class="text-muted small">Emision: {{ $nomina->fecha_pago->format('d/m/Y') }}</div>
                <div class="mt-2">@include('partials.badge',['estado'=>$nomina->estado])</div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="small text-muted text-uppercase fw-bold mb-1">Empleado</div>
                <div class="fw-bold fs-6">{{ $nomina->empleado->nombre_completo }}</div>
                <div class="text-muted small">Departamento: {{ $nomina->empleado->departamento->nombre }}</div>
                <div class="text-muted small">Puesto: {{ $nomina->empleado->puesto->nombre }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted text-uppercase fw-bold mb-1">Periodo</div>
                <div class="fw-bold">{{ $nomina->periodo_pago }}</div>
                <div class="text-muted small">RFC: {{ $nomina->empleado->rfc }}</div>
                <div class="text-muted small">Metodo de pago: {{ $nomina->metodo_pago_label }}</div>
            </div>
        </div>
        <div class="table-responsive mb-4">
            <table class="table align-middle">
                <thead>
                    <tr class="table-light">
                        <th>Concepto</th>
                        <th class="text-center">Ingresos</th>
                        <th class="text-center">Deducciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Sueldo base</td><td class="text-center">${{ number_format($nomina->sueldo_base,2) }}</td><td></td></tr>
                    <tr><td>Bonos</td><td class="text-center">${{ number_format($nomina->bonos,2) }}</td><td></td></tr>
                    <tr><td>Horas extra</td><td class="text-center">${{ number_format($nomina->horas_extra,2) }}</td><td></td></tr>
                    <tr><td>Deducciones</td><td></td><td class="text-center">${{ number_format($nomina->deducciones,2) }}</td></tr>
                    <tr><td>ISR</td><td></td><td class="text-center">${{ number_format($nomina->isr,2) }}</td></tr>
                    <tr><td>IMSS</td><td></td><td class="text-center">${{ number_format($nomina->imss,2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th class="fs-5">Total a pagar</th>
                        <th colspan="2" class="text-center fs-5 fw-bold">${{ number_format($nomina->total_pagar,2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="row mb-5">
            <div class="col-md-8 text-muted small">
                <div>Generada por: {{ $nomina->creador?->name ?? '—' }} el {{ optional($nomina->created_at)->format('d/m/Y H:i') }}</div>
                @if($nomina->estado === 'pagada')
                    <div>Pagada por: {{ $nomina->pagador?->name ?? '—' }} el {{ optional($nomina->fecha_pago_real)->format('d/m/Y H:i') }}</div>
                @endif
            </div>
            <div class="col-md-4 text-center"><div class="small text-uppercase text-muted fw-bold mb-5">Firma de recepcion</div><hr class="m-0"></div>
        </div>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('nominas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Imprimir recibo</button>
    </div>
</div>
@endsection