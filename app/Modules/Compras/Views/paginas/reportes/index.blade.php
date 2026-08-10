@extends('layouts.app')

@section('title', 'Reportes de compras')
@section('header', 'Reportes de compras')
@section('subtitle', 'Consultas de solo lectura, imprimibles y exportables')

@php
    $reportes = [
        ['ruta' => 'compras.reportes.por-periodo', 'titulo' => 'Compras por periodo', 'icono' => 'calendar-range', 'descripcion' => 'Lo facturado en un rango de fechas, factura por factura.'],
        ['ruta' => 'compras.reportes.por-proveedor', 'titulo' => 'Compras por proveedor', 'icono' => 'shop', 'descripcion' => 'Cuanto se le compro a cada quien y cuanto se le debe.'],
        ['ruta' => 'compras.reportes.por-producto', 'titulo' => 'Compras por producto', 'icono' => 'box-seam', 'descripcion' => 'Que se compra mas y a que costo promedio.'],
        ['ruta' => 'compras.reportes.pendientes-por-recibir', 'titulo' => 'Pendientes por recibir', 'icono' => 'truck', 'descripcion' => 'Lo que se pidio y todavia no llega, con su importe.'],
        ['ruta' => 'compras.reportes.cuentas-por-pagar', 'titulo' => 'Cuentas por pagar', 'icono' => 'cash-stack', 'descripcion' => 'Saldos por vencer y vencidos, ordenados por urgencia.'],
    ];
@endphp

@section('content')
    <x-page-header title="Reportes de compras" subtitle="Compras / Reportes" />

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
