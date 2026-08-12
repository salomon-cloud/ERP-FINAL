@extends('layouts.app')

@section('title', 'CRM')
@section('header', 'CRM')
@section('subtitle', 'Prospectos, oportunidades, contactos y seguimiento')

@section('content')
    <x-page-header title="CRM" subtitle="Embudo comercial y seguimiento">
        <a class="btn btn-outline-primary" href="{{ route('crm.prospectos.index') }}">
            <i class="bi bi-person-badge me-1"></i>Prospectos
        </a>
        <a class="btn btn-primary" href="{{ route('crm.oportunidades.index') }}">
            <i class="bi bi-diagram-3 me-1"></i>Oportunidades
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><x-stat-card label="Prospectos nuevos" :value="(string) $prospectosNuevos" icon="person-badge" /></div>
        <div class="col-md-3"><x-stat-card label="Oportunidades abiertas" :value="(string) $oportunidadesAbiertas" icon="diagram-3" /></div>
        <div class="col-md-3"><x-stat-card label="Monto en embudo" :value="'$'.number_format($montoEmbudo, 2)" icon="cash-stack" /></div>
        <div class="col-md-3"><x-stat-card label="Tareas pendientes" :value="(string) $tareasPendientes" icon="check2-square" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-card title="Embudo por etapa">
                @forelse ($oportunidadesPorEtapa as $fila)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="text-capitalize">{{ str_replace('_', ' ', $fila->etapa) }}</span>
                        <span class="fw-bold">{{ $fila->total }} / ${{ number_format((float) $fila->monto, 2) }}</span>
                    </div>
                @empty
                    <x-empty message="Todavia no hay oportunidades." icon="diagram-3" />
                @endforelse
            </x-card>
        </div>
        <div class="col-lg-7">
            <x-card title="Actividades recientes">
                @forelse ($actividadesRecientes as $actividad)
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <div>
                            <div class="fw-bold text-capitalize">{{ $actividad->tipo_actividad->label() }}</div>
                            <div class="small text-muted">{{ $actividad->resumen }}</div>
                        </div>
                        <small class="text-muted">{{ optional($actividad->created_at)->format('d/m/Y H:i') }}</small>
                    </div>
                @empty
                    <x-empty message="Aun no hay actividades registradas." icon="calendar-check" />
                @endforelse
            </x-card>
        </div>
    </div>
@endsection