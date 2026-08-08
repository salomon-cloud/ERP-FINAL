@extends('layouts.app')

@section('title', 'Evaluacion')
@section('header', 'Evaluacion de desempeno')

@section('content')
    <x-page-header :title="$evaluacion->empleado?->nombre_completo ?? 'Evaluacion'"
                   :subtitle="'RH / Evaluaciones / '.$evaluacion->periodo_evaluado">
        <a class="btn btn-outline-primary" href="{{ route('rh.evaluaciones.edit', $evaluacion) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <x-card title="Resultado" class="mb-3">
        <dl class="row mb-0">
            <dt class="col-sm-3">Empleado</dt><dd class="col-sm-9">{{ $evaluacion->empleado?->nombre_completo ?? '--' }}</dd>
            <dt class="col-sm-3">Evaluador</dt><dd class="col-sm-9">{{ $evaluacion->evaluador?->nombre_completo ?? '--' }}</dd>
            <dt class="col-sm-3">Periodo</dt><dd class="col-sm-9">{{ $evaluacion->periodo_evaluado }}</dd>
            <dt class="col-sm-3">Calificacion</dt>
            <dd class="col-sm-9 fw-bold">
                {{ $evaluacion->calificacion !== null ? number_format((float) $evaluacion->calificacion, 2).' / 100' : 'Sin calificar' }}
            </dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$evaluacion->estado->color()" :label="$evaluacion->estado->label()" /></dd>
            <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">{{ $evaluacion->evaluado_en?->format('d/m/Y') ?? '--' }}</dd>
        </dl>
    </x-card>

    <div class="row g-3">
        <div class="col-md-6">
            <x-card title="Fortalezas">
                <p class="mb-0">{{ $evaluacion->fortalezas ?? 'Sin registrar.' }}</p>
            </x-card>
        </div>
        <div class="col-md-6">
            <x-card title="Areas de mejora">
                <p class="mb-0">{{ $evaluacion->areas_mejora ?? 'Sin registrar.' }}</p>
            </x-card>
        </div>
    </div>

    @if (! empty($evaluacion->objetivos))
        <x-card title="Objetivos" class="mt-3">
            <x-table :head="['Objetivo', 'Metrica', 'Meta', 'Logrado']">
                @foreach ($evaluacion->objetivos as $objetivo)
                    <tr>
                        <td>{{ $objetivo['objetivo'] ?? '--' }}</td>
                        <td>{{ $objetivo['metrica'] ?? '--' }}</td>
                        <td>{{ $objetivo['meta'] ?? '--' }}</td>
                        <td>{{ ! empty($objetivo['logrado']) ? 'Si' : 'No' }}</td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    @endif
@endsection
