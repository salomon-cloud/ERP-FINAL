@extends('layouts.app')

@section('title', 'Compras')
@section('header', 'Compras')
@section('subtitle', 'Proveedores, requisiciones, ordenes de compra y pagos')

@section('content')
    <x-page-header title="Tablero de Compras" subtitle="Compras / Tablero">
        <a class="btn btn-primary" href="{{ route('compras.ordenes.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva orden
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Por autorizar" :value="number_format($requisicionesPorRevisar)" icon="clipboard-check"
                hint="Requisiciones enviadas"
                :href="route('compras.requisiciones.index', ['estado' => 'enviada'])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Ordenes por recibir" :value="number_format($ordenesPendientes)" icon="truck"
                :href="route('compras.reportes.pendientes-por-recibir')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Por pagar" :value="'$'.number_format($porPagar, 2)" icon="cash-stack"
                :href="route('compras.reportes.cuentas-por-pagar')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Facturas vencidas" :value="number_format($vencidas)" icon="exclamation-triangle"
                hint="Con vencimiento pasado y saldo"
                :href="route('compras.reportes.cuentas-por-pagar')" />
        </div>
    </div>

    <x-card class="mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('compras.requisiciones.create') }}">
                <i class="bi bi-clipboard-plus me-1"></i>Nueva requisicion
            </a>
            <a class="btn btn-outline-primary" href="{{ route('compras.recepciones.create') }}">
                <i class="bi bi-box-arrow-in-down me-1"></i>Registrar recepcion
            </a>
            <a class="btn btn-outline-primary" href="{{ route('compras.facturas.create') }}">
                <i class="bi bi-receipt me-1"></i>Capturar factura
            </a>
            <a class="btn btn-outline-primary" href="{{ route('compras.pagos.create') }}">
                <i class="bi bi-cash-coin me-1"></i>Registrar pago
            </a>
        </div>
    </x-card>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-card title="Requisiciones por autorizar">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('compras.requisiciones.index', ['estado' => 'enviada']) }}">Ver todas</a>
                </x-slot:actions>

                <x-table :head="['Folio', 'Area', 'Solicita', 'Requerida', '']">
                    @forelse ($solicitudesPendientes as $requisicion)
                        <tr>
                            <td class="fw-bold">{{ $requisicion->numero_requisicion }}</td>
                            <td>{{ $requisicion->departamento?->nombre ?? '--' }}</td>
                            <td class="small text-muted">{{ $requisicion->solicitante?->name ?? '--' }}</td>
                            <td class="small">{{ $requisicion->fecha_requerida?->format('d/m/Y') ?? '--' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn"
                                   href="{{ route('compras.requisiciones.show', $requisicion) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="No hay requisiciones esperando autorizacion." icon="check2-circle" />
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card title="Ordenes por recibir">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('compras.ordenes.index') }}">Ver todas</a>
                </x-slot:actions>

                <x-table :head="['Folio', 'Proveedor', 'Entrega', 'Estado', '']">
                    @forelse ($ordenesPorRecibir as $orden)
                        <tr>
                            <td class="fw-bold">{{ $orden->numero_orden }}</td>
                            <td>{{ $orden->proveedor?->nombre ?? '--' }}</td>
                            <td class="small">{{ $orden->fecha_entrega?->format('d/m/Y') ?? '--' }}</td>
                            <td><x-badge :estado="$orden->estado->color()" :label="$orden->estado->label()" /></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn"
                                   href="{{ route('compras.ordenes.show', $orden) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="No hay ordenes esperando mercancia." icon="truck" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>

    <x-card title="Facturas por pagar" class="mt-3">
        <x-slot:actions>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('compras.reportes.cuentas-por-pagar') }}">Ver reporte</a>
        </x-slot:actions>

        <x-table :head="['Folio', 'Proveedor', 'Vencimiento', ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($facturasPorPagar as $factura)
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->proveedor?->nombre ?? '--' }}</td>
                    <td class="small {{ $factura->esta_vencida ? 'text-danger fw-bold' : '' }}">
                        {{ $factura->fecha_vencimiento?->format('d/m/Y') ?? '--' }}
                    </td>
                    <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($factura->saldo, 2) }}</td>
                    <td><x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn"
                           href="{{ route('compras.facturas.show', $factura) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay facturas pendientes de pago." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
