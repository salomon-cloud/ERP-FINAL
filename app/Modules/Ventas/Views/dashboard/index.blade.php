@extends('layouts.app')

@section('title', 'Ventas')
@section('header', 'Ventas')
@section('subtitle', 'Clientes, cotizaciones, pedidos, facturas y cobranza')

@section('content')
    <x-page-header title="Tablero de Ventas" subtitle="Ventas / Tablero">
        <a class="btn btn-primary" href="{{ route('ventas.pedidos.create') }}">
            <i class="bi bi-cart-plus me-1"></i>Nueva venta
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Vendido este mes" :value="'$'.number_format($ventasDelMes, 2)" icon="graph-up-arrow"
                :href="route('ventas.reportes.por-periodo', ['desde' => now()->startOfMonth()->toDateString()])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Pedidos por surtir" :value="number_format($porSurtir)" icon="box-seam"
                hint="Confirmados con existencia apartada"
                :href="route('ventas.pedidos.index', ['estado' => 'confirmado'])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Por cobrar" :value="'$'.number_format($porCobrar, 2)" icon="cash-coin"
                :href="route('ventas.reportes.cuentas-por-cobrar')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Facturas vencidas" :value="number_format($vencidas)" icon="exclamation-triangle"
                hint="Con vencimiento pasado y saldo"
                :href="route('ventas.facturas.index', ['solo_vencidas' => 1])" />
        </div>
    </div>

    <x-card class="mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('ventas.cotizaciones.create') }}">
                <i class="bi bi-file-earmark-plus me-1"></i>Nueva cotizacion
            </a>
            <a class="btn btn-outline-primary" href="{{ route('ventas.clientes.create') }}">
                <i class="bi bi-person-plus me-1"></i>Nuevo cliente
            </a>
            <a class="btn btn-outline-primary" href="{{ route('ventas.cobros.create') }}">
                <i class="bi bi-cash-coin me-1"></i>Registrar cobro
            </a>
            <a class="btn btn-outline-primary" href="{{ route('ventas.facturas.index') }}">
                <i class="bi bi-receipt me-1"></i>Ver facturas
            </a>
        </div>
    </x-card>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-card title="Pedidos por surtir" subtitle="Con existencia ya apartada">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('ventas.pedidos.index', ['estado' => 'confirmado']) }}">Ver todos</a>
                </x-slot:actions>

                <x-table :head="['Folio', 'Cliente', 'Entrega', ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($pedidosPorSurtir as $pedido)
                        <tr>
                            <td class="fw-bold">{{ $pedido->numero_pedido }}</td>
                            <td>{{ $pedido->cliente?->nombre ?? '--' }}</td>
                            <td class="small">{{ $pedido->fecha_entrega?->format('d/m/Y') ?? '--' }}</td>
                            <td class="text-end">${{ number_format((float) $pedido->total, 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn"
                                   href="{{ route('ventas.pedidos.show', $pedido) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="No hay pedidos esperando surtido." icon="check2-circle" />
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card title="Cotizaciones abiertas" subtitle="Enviadas y aceptadas">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.cotizaciones.index') }}">Ver todas</a>
                </x-slot:actions>

                <x-table :head="['Folio', 'Cliente', 'Vigencia', 'Estado', '']">
                    @forelse ($cotizacionesAbiertas as $cotizacion)
                        <tr>
                            <td class="fw-bold">{{ $cotizacion->numero_cotizacion }}</td>
                            <td>{{ $cotizacion->cliente?->nombre ?? '--' }}</td>
                            <td class="small {{ $cotizacion->esta_vencida ? 'text-danger fw-bold' : '' }}">
                                {{ $cotizacion->vigencia?->format('d/m/Y') ?? '--' }}
                            </td>
                            <td>
                                @if ($cotizacion->esta_vencida)
                                    <x-badge estado="retardo" label="Vencida" />
                                @else
                                    <x-badge :estado="$cotizacion->estado->color()" :label="$cotizacion->estado->label()" />
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn"
                                   href="{{ route('ventas.cotizaciones.show', $cotizacion) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="No hay cotizaciones abiertas." icon="file-earmark-text" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>

    <x-card title="Facturas por cobrar" class="mt-3">
        <x-slot:actions>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.reportes.cuentas-por-cobrar') }}">Ver reporte</a>
        </x-slot:actions>

        <x-table :head="['Folio', 'Cliente', 'Vencimiento', ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($facturasPorCobrar as $factura)
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->cliente?->nombre ?? '--' }}</td>
                    <td class="small {{ $factura->esta_vencida ? 'text-danger fw-bold' : '' }}">
                        {{ $factura->fecha_vencimiento?->format('d/m/Y') ?? '--' }}
                    </td>
                    <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($factura->saldo, 2) }}</td>
                    <td>
                        @if ($factura->esta_vencida)
                            <x-badge estado="retardo" label="Vencida" />
                        @else
                            <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" />
                        @endif
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn"
                           href="{{ route('ventas.cobros.create', ['factura_id' => $factura->id]) }}"
                           title="Cobrar"><i class="bi bi-cash-coin"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay facturas pendientes de cobro." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
