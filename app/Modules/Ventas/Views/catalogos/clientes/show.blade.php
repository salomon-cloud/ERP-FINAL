@extends('layouts.app')

@section('title', 'Cliente')
@section('header', $cliente->nombre)
@section('subtitle', $cliente->codigo)

@section('content')
    <x-page-header :title="$cliente->nombre" :subtitle="'Ventas / Clientes / '.$cliente->codigo">
        <a class="btn btn-outline-primary" href="{{ route('ventas.cotizaciones.create', ['cliente_id' => $cliente->id]) }}">
            <i class="bi bi-file-earmark-plus me-1"></i>Cotizar
        </a>
        <a class="btn btn-primary" href="{{ route('ventas.pedidos.create', ['cliente_id' => $cliente->id]) }}">
            <i class="bi bi-cart-plus me-1"></i>Nueva venta
        </a>
        <a class="btn btn-outline-primary" href="{{ route('ventas.clientes.edit', $cliente) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    {{-- Los KPI salen de v_historial_cliente, la misma vista que lee CRM: asi
         los dos modulos pintan exactamente las mismas cifras. --}}
    @if ($historial)
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <x-stat-card label="Facturado" :value="'$'.number_format((float) $historial->monto_facturado, 2)" icon="receipt" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Cobrado" :value="'$'.number_format((float) $historial->monto_cobrado, 2)" icon="cash-coin" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Saldo pendiente" :value="'$'.number_format((float) $historial->saldo_pendiente, 2)" icon="hourglass-split"
                    :href="route('ventas.reportes.cuentas-por-cobrar', ['cliente_id' => $cliente->id])" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Acreditado" :value="'$'.number_format((float) $historial->monto_acreditado, 2)" icon="arrow-return-left"
                    hint="Notas de credito emitidas" />
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card title="Datos del cliente">
                <dl class="row mb-0">
                    <dt class="col-5">Codigo</dt><dd class="col-7">{{ $cliente->codigo }}</dd>
                    <dt class="col-5">Razon social</dt><dd class="col-7">{{ $cliente->razon_social ?: '--' }}</dd>
                    <dt class="col-5">RFC</dt><dd class="col-7">{{ $cliente->rfc ?: '--' }}</dd>
                    <dt class="col-5">Correo</dt><dd class="col-7">{{ $cliente->correo ?: '--' }}</dd>
                    <dt class="col-5">Telefono</dt><dd class="col-7">{{ $cliente->telefono ?: '--' }}</dd>
                    <dt class="col-5">Condicion</dt><dd class="col-7">{{ $cliente->condicionPago?->nombre ?? '--' }}</dd>
                    <dt class="col-5">Lista</dt><dd class="col-7">{{ $cliente->listaPrecio?->nombre ?? 'Predeterminada' }}</dd>
                    <dt class="col-5">Moneda</dt><dd class="col-7">{{ $cliente->moneda }}</dd>
                    <dt class="col-5">Limite de credito</dt>
                    <dd class="col-7">${{ number_format((float) $cliente->limite_credito, 2) }}</dd>
                    <dt class="col-5">Credito disponible</dt>
                    <dd class="col-7 fw-bold {{ $cliente->credito_disponible < 0 ? 'text-danger' : '' }}">
                        ${{ number_format($cliente->credito_disponible, 2) }}
                    </dd>
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7"><x-badge :estado="$cliente->estado->color()" :label="$cliente->estado->label()" /></dd>
                    @if ($cliente->direccion)
                        <dt class="col-5">Direccion</dt><dd class="col-7">{{ $cliente->direccion }}</dd>
                    @endif
                </dl>
            </x-card>
        </div>

        <div class="col-lg-8">
            <x-card title="Facturas" class="mb-3">
                <x-table :head="['Folio', 'Emision', ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
                    @forelse ($facturas as $factura)
                        <tr>
                            <td class="fw-bold">{{ $factura->numero_factura }}</td>
                            <td class="small">{{ $factura->fecha_emision?->format('d/m/Y') }}</td>
                            <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                            <td class="text-end">${{ number_format($factura->saldo, 2) }}</td>
                            <td><x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" /></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.facturas.show', $factura) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="6" message="Todavia no se le ha facturado nada." icon="receipt" />
                    @endforelse
                </x-table>
            </x-card>

            <div class="row g-3">
                <div class="col-md-4">
                    <x-card title="Cotizaciones">
                        @forelse ($cotizaciones as $cotizacion)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <a href="{{ route('ventas.cotizaciones.show', $cotizacion) }}">{{ $cotizacion->numero_cotizacion }}</a>
                                <x-badge :estado="$cotizacion->estado->color()" :label="$cotizacion->estado->label()" />
                            </div>
                        @empty
                            <x-empty message="Sin cotizaciones." icon="file-earmark-text" />
                        @endforelse
                    </x-card>
                </div>
                <div class="col-md-4">
                    <x-card title="Pedidos">
                        @forelse ($pedidos as $pedido)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <a href="{{ route('ventas.pedidos.show', $pedido) }}">{{ $pedido->numero_pedido }}</a>
                                <x-badge :estado="$pedido->estado->color()" :label="$pedido->estado->label()" />
                            </div>
                        @empty
                            <x-empty message="Sin pedidos." icon="cart-check" />
                        @endforelse
                    </x-card>
                </div>
                <div class="col-md-4">
                    <x-card title="Cobros">
                        @forelse ($cobros as $cobro)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <a href="{{ route('ventas.cobros.show', $cobro) }}">{{ $cobro->numero_cobro }}</a>
                                <span class="small">${{ number_format((float) $cobro->monto, 2) }}</span>
                            </div>
                        @empty
                            <x-empty message="Sin cobros." icon="cash-coin" />
                        @endforelse
                    </x-card>
                </div>
            </div>
        </div>
    </div>
@endsection
