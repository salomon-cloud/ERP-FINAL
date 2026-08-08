@extends('layouts.app')

@section('title', 'Reportes de RH')
@section('header', 'Reportes de RH')
@section('subtitle', 'Consultas de solo lectura, imprimibles y exportables')

@php
    $reportes = [
        ['ruta' => 'rh.reportes.empleados', 'titulo' => 'Plantilla de empleados', 'icono' => 'people', 'descripcion' => 'Quien esta contratado, en que area y con que sueldo.'],
        ['ruta' => 'rh.reportes.asistencias', 'titulo' => 'Asistencia', 'icono' => 'calendar-check', 'descripcion' => 'Entradas, salidas y horas trabajadas en un rango.'],
        ['ruta' => 'rh.reportes.permisos', 'titulo' => 'Permisos y vacaciones', 'icono' => 'calendar2-week', 'descripcion' => 'Solicitudes y los dias sin goce que pegan a la nomina.'],
        ['ruta' => 'rh.reportes.nomina', 'titulo' => 'Nomina pagada', 'icono' => 'cash-stack', 'descripcion' => 'Recibos por periodo, con percepciones y deducciones.'],
        ['ruta' => 'rh.reportes.contratos-por-vencer', 'titulo' => 'Contratos por vencer', 'icono' => 'file-earmark-excel', 'descripcion' => 'Vigencias proximas a terminar, para no olvidar una renovacion.'],
        ['ruta' => 'rh.reportes.plantilla-por-departamento', 'titulo' => 'Plantilla por departamento', 'icono' => 'diagram-3', 'descripcion' => 'Cuanta gente y cuanto cuesta cada area.'],
    ];
@endphp

@section('content')
    <x-page-header title="Reportes de Recursos Humanos" subtitle="RH / Reportes" />

    <div class="row g-3">
        @foreach ($reportes as $reporte)
            <div class="col-md-6 col-xl-4">
                <x-card class="h-100">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon"><i class="bi bi-{{ $reporte['icono'] }}"></i></div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $reporte['titulo'] }}</h5>
                            <p class="text-muted small mb-3">{{ $reporte['descripcion'] }}</p>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route($reporte['ruta']) }}">
                                Abrir <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </x-card>
            </div>
        @endforeach
    </div>
@endsection
