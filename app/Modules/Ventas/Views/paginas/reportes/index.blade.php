@extends('layouts.app')

@section('title', 'Reportes de ventas')
@section('header', 'Reportes de ventas')
@section('subtitle', 'Consultas de solo lectura, imprimibles y exportables')

@php
    $reportes = [
        ['ruta' => 'ventas.reportes.por-periodo', 'titulo' => 'Ventas por periodo', 'icono' => 'calendar-range', 'descripcion' => 'Lo facturado en un rango de fechas, factura por factura.'],
        ['ruta' => 'ventas.reportes.por-cliente', 'titulo' => 'Ventas por cliente', 'icono' => 'people', 'descripcion' => 'Cuanto compro cada quien y cuanto debe.'],
        ['ruta' => 'ventas.reportes.por-producto', 'titulo' => 'Ventas por producto', 'icono' => 'box-seam', 'descripcion' => 'Que se vende mas y a que precio promedio.'],
        ['ruta' => 'ventas.reportes.cuentas-por-cobrar', 'titulo' => 'Cuentas por cobrar', 'icono' => 'cash-coin', 'descripcion' => 'Saldos por vencer y vencidos, ordenados por urgencia.'],
        ['ruta' => 'ventas.reportes.devoluciones', 'titulo' => 'Devoluciones y notas', 'icono' => 'arrow-return-left', 'descripcion' => 'Notas de credito emitidas, agrupadas por motivo.'],
    ];
@endphp

@section('content')
    <x-page-header title="Reportes de ventas" subtitle="Ventas / Reportes" />

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
