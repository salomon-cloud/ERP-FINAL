@extends('layouts.app')

@section('title', 'Contrato')
@section('header', 'Contrato')

@section('content')
    <x-page-header :title="$contrato->empleado?->nombre_completo ?? 'Contrato'" subtitle="RH / Contratos / Detalle">
        <a class="btn btn-outline-primary" href="{{ route('rh.contratos.edit', $contrato) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </x-page-header>

    <x-card title="Datos del contrato">
        <dl class="row mb-0">
            <dt class="col-sm-3">Numero</dt><dd class="col-sm-9">{{ $contrato->numero_contrato ?? '--' }}</dd>
            <dt class="col-sm-3">Empleado</dt><dd class="col-sm-9">{{ $contrato->empleado?->nombre_completo ?? '--' }}</dd>
            <dt class="col-sm-3">Tipo</dt><dd class="col-sm-9">{{ $contrato->tipo_contrato->label() }}</dd>
            <dt class="col-sm-3">Vigencia</dt>
            <dd class="col-sm-9">
                {{ $contrato->fecha_inicio->format('d/m/Y') }} -
                {{ $contrato->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}
            </dd>
            <dt class="col-sm-3">Sueldo del contrato</dt>
            <dd class="col-sm-9">
                ${{ number_format((float) $contrato->sueldo, 2) }}
                <small class="text-muted d-block">Dato legal e historico; la nomina paga el sueldo base del empleado.</small>
            </dd>
            <dt class="col-sm-3">Jornada</dt><dd class="col-sm-9">{{ (float) $contrato->jornada_horas }} horas</dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$contrato->estado->color()" :label="$contrato->estado->label()" /></dd>
            <dt class="col-sm-3">Firmado</dt><dd class="col-sm-9">{{ $contrato->firmado_en?->format('d/m/Y') ?? 'Sin firmar' }}</dd>
            <dt class="col-sm-3">Clausulas</dt><dd class="col-sm-9">{{ $contrato->resumen_clausulas ?? '--' }}</dd>
        </dl>
    </x-card>
@endsection
