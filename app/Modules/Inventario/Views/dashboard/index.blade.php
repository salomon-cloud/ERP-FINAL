@extends('layouts.app')

@section('title', 'Inventario')
@section('header', 'Inventario')
@section('subtitle', 'Productos, almacenes, movimientos, conteos y minimos')

@section('content')
    <x-page-header title="Tablero de Inventario" subtitle="Inventario / Tablero">
        <a class="btn btn-primary" href="{{ route('inventario.productos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo producto
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Productos activos" :value="number_format($productosActivos)" icon="box-seam"
                :href="route('inventario.productos.index', ['estado' => 'activo'])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Bajo minimo" :value="number_format($bajoMinimo)" icon="exclamation-triangle"
                hint="Disponible por debajo de la regla de reorden"
                :href="route('inventario.reportes.stock-bajo')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Valor del inventario" :value="'$'.number_format($valorInventario, 2)" icon="cash-coin"
                hint="Existencia fisica por costo"
                :href="route('inventario.reportes.valoracion')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Lotes por vencer" :value="number_format($lotesPorVencer)" icon="hourglass-split"
                hint="En los proximos 30 dias"
                :href="route('inventario.reportes.caducidades')" />
        </div>
    </div>

    <x-card class="mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('inventario.existencias.index') }}">
                <i class="bi bi-clipboard-data me-1"></i>Consultar existencias
            </a>
            <a class="btn btn-outline-primary" href="{{ route('inventario.traspasos.create') }}">
                <i class="bi bi-arrow-left-right me-1"></i>Nuevo traspaso
                @if ($traspasosEnTransito > 0)
                    <span class="badge bg-info ms-1">{{ $traspasosEnTransito }} en transito</span>
                @endif
            </a>
            <a class="btn btn-outline-primary" href="{{ route('inventario.ajustes.create') }}">
                <i class="bi bi-sliders me-1"></i>Nuevo ajuste
                @if ($ajustesPendientes > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $ajustesPendientes }} sin aplicar</span>
                @endif
            </a>
            <a class="btn btn-outline-primary" href="{{ route('inventario.conteos.create') }}">
                <i class="bi bi-ui-checks me-1"></i>Nuevo conteo
                @if ($conteosAbiertos > 0)
                    <span class="badge bg-secondary ms-1">{{ $conteosAbiertos }} abiertos</span>
                @endif
            </a>
            <a class="btn btn-outline-primary" href="{{ route('inventario.movimientos.index') }}">
                <i class="bi bi-journal-text me-1"></i>Ver kardex
            </a>
        </div>
    </x-card>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-card title="Hay que comprar" subtitle="Productos por debajo de su minimo">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('inventario.reportes.stock-bajo') }}">Ver todos</a>
                </x-slot:actions>

                <x-table :head="['Producto', 'Almacen', ['label' => 'Disponible', 'align' => 'end'], ['label' => 'Minimo', 'align' => 'end']]">
                    @forelse ($productosBajoMinimo as $fila)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $fila->producto_nombre }}</div>
                                <small class="text-muted">{{ $fila->sku }}</small>
                            </td>
                            <td>{{ $fila->almacen_codigo }}</td>
                            <td class="text-end text-danger fw-bold">{{ (float) $fila->disponible }}</td>
                            <td class="text-end">{{ (float) $fila->cantidad_minima }}</td>
                        </tr>
                    @empty
                        <x-empty :colspan="4" message="Todo esta por encima de su minimo." icon="check2-circle" />
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Proximos a caducar">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('inventario.reportes.caducidades') }}">Ver todos</a>
                </x-slot:actions>

                @forelse ($proximosAVencer as $lote)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <div>
                            <div class="small fw-bold">{{ $lote->producto?->nombre ?? '--' }}</div>
                            <small class="text-muted">Lote {{ $lote->numero_lote }}</small>
                        </div>
                        <span class="badge-soft {{ $lote->esta_caducado ? 'badge-cancelada' : 'badge-pendiente' }}">
                            {{ $lote->fecha_caducidad?->format('d/m/Y') }}
                        </span>
                    </div>
                @empty
                    <x-empty message="Ningun lote caduca pronto." icon="check2-circle" />
                @endforelse
            </x-card>
        </div>
    </div>

    <x-card title="Ultimos movimientos" class="mt-3">
        <x-slot:actions>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('inventario.movimientos.index') }}">Ver kardex</a>
        </x-slot:actions>

        <x-table :head="['Fecha', 'Tipo', 'Producto', 'Almacen', ['label' => 'Cantidad', 'align' => 'end'], 'Usuario']">
            @forelse ($ultimosMovimientos as $movimiento)
                <tr>
                    <td class="small text-muted">{{ $movimiento->aplicado_en?->format('d/m/Y H:i') }}</td>
                    <td><x-badge :estado="$movimiento->tipo_movimiento->color()" :label="$movimiento->tipo_movimiento->label()" /></td>
                    <td>{{ $movimiento->producto?->nombre ?? '--' }}</td>
                    <td>{{ $movimiento->almacen?->codigo ?? '--' }}</td>
                    <td class="text-end fw-bold {{ (float) $movimiento->cantidad < 0 ? 'text-danger' : 'text-success' }}">
                        {{ (float) $movimiento->cantidad > 0 ? '+' : '' }}{{ (float) $movimiento->cantidad }}
                    </td>
                    <td class="small text-muted">{{ $movimiento->aplicadoPor?->name ?? '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="6" message="Todavia no hay movimientos de inventario." icon="journal-text" />
            @endforelse
        </x-table>
    </x-card>
@endsection
