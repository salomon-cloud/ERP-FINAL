@extends('layouts.app')

@section('title', 'Reportes de inventario')
@section('header', 'Reportes de inventario')
@section('subtitle', 'Consultas de solo lectura, imprimibles y exportables')

@php
    $reportes = [
        ['ruta' => 'inventario.reportes.existencias', 'titulo' => 'Existencias valorizadas', 'icono' => 'clipboard-data', 'descripcion' => 'Que hay, donde esta, cuanto esta apartado y cuanto vale.'],
        ['ruta' => 'inventario.reportes.movimientos', 'titulo' => 'Kardex por periodo', 'icono' => 'journal-text', 'descripcion' => 'Todos los movimientos de un rango de fechas.'],
        ['ruta' => 'inventario.reportes.stock-bajo', 'titulo' => 'Stock bajo', 'icono' => 'exclamation-triangle', 'descripcion' => 'Lo que esta por debajo de su minimo y cuanto conviene pedir.'],
        ['ruta' => 'inventario.reportes.valoracion', 'titulo' => 'Valoracion por categoria', 'icono' => 'cash-coin', 'descripcion' => 'Cuanto dinero hay parado en cada familia de producto.'],
        ['ruta' => 'inventario.reportes.caducidades', 'titulo' => 'Proximos a caducar', 'icono' => 'hourglass-split', 'descripcion' => 'Lotes vencidos o a punto de vencer.'],
    ];
@endphp

@section('content')
    <x-page-header title="Reportes de inventario" subtitle="Inventario / Reportes" />

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
