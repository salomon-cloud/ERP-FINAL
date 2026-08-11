@extends('layouts.app')

@section('title', 'Cliente')
@section('header', $cliente->nombre)
@section('subtitle', $cliente->codigo)

@section('content')
    <x-page-header :title="$cliente->nombre" :subtitle="'CRM / Clientes / '.$cliente->codigo">
        <a class="btn btn-outline-primary" href="{{ route('ventas.cotizaciones.create', ['cliente_id' => $cliente->id]) }}">
            <i class="bi bi-file-earmark-plus me-1"></i>Cotizar
        </a>
        <a class="btn btn-primary" href="{{ route('ventas.pedidos.create', ['cliente_id' => $cliente->id]) }}">
            <i class="bi bi-cart-plus me-1"></i>Nueva venta
        </a>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.clientes.show', $cliente) }}">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ficha en Ventas
        </a>
    </x-page-header>

    {{-- Los KPI salen de v_historial_cliente, la misma vista que lee Ventas: asi
         los dos modulos pintan exactamente las mismas cifras. --}}
    @if ($historial)
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <x-stat-card label="Facturado" :value="'$'.number_format((float) $historial->monto_facturado, 2)" icon="receipt"
                    :href="route('ventas.facturas.index', ['cliente_id' => $cliente->id])" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Saldo pendiente" :value="'$'.number_format((float) $historial->saldo_pendiente, 2)" icon="hourglass-split"
                    :href="route('ventas.reportes.cuentas-por-cobrar', ['cliente_id' => $cliente->id])" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Credito disponible" :value="'$'.number_format($cliente->credito_disponible, 2)" icon="credit-card" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Oportunidades abiertas" :value="(string) $historial->oportunidades_abiertas" icon="diagram-3" />
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <x-card title="Datos del cliente">
                <dl class="row mb-0">
                    <dt class="col-5">Codigo</dt><dd class="col-7">{{ $cliente->codigo }}</dd>
                    <dt class="col-5">Nombre</dt><dd class="col-7">{{ $cliente->nombre }}</dd>
                    <dt class="col-5">Razon social</dt><dd class="col-7">{{ $cliente->razon_social ?: '--' }}</dd>
                    <dt class="col-5">RFC</dt><dd class="col-7">{{ $cliente->rfc ?: '--' }}</dd>
                    <dt class="col-5">Correo</dt><dd class="col-7">{{ $cliente->correo ?: '--' }}</dd>
                    <dt class="col-5">Telefono</dt><dd class="col-7">{{ $cliente->telefono ?: '--' }}</dd>
                    <dt class="col-5">Condicion de pago</dt><dd class="col-7">{{ $cliente->condicionPago?->nombre ?? '--' }}</dd>
                    <dt class="col-5">Lista de precios</dt><dd class="col-7">{{ $cliente->listaPrecio?->nombre ?? 'Predeterminada' }}</dd>
                    <dt class="col-5">Moneda</dt><dd class="col-7">{{ $cliente->moneda }}</dd>
                    <dt class="col-5">Limite de credito</dt>
                    <dd class="col-7">${{ number_format((float) $cliente->limite_credito, 2) }}</dd>
                    <dt class="col-5">Saldo pendiente</dt>
                    <dd class="col-7 fw-bold">${{ number_format($cliente->saldo_pendiente, 2) }}</dd>
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

        <div class="col-lg-7">
            <x-card title="Historial comercial" subtitle="Los documentos viven en Ventas; aqui se enlazan para no duplicar la logica.">
                @if ($historial)
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('ventas.facturas.index', ['cliente_id' => $cliente->id]) }}">
                            <i class="bi bi-receipt me-2"></i>Facturas
                        </a>
                        <span class="small text-muted">{{ $historial->facturas }} factura(s)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('ventas.pedidos.index', ['cliente_id' => $cliente->id]) }}">
                            <i class="bi bi-cart-check me-2"></i>Pedidos
                        </a>
                        <span class="small text-muted">{{ $historial->pedidos }} pedido(s)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('ventas.cotizaciones.index', ['cliente_id' => $cliente->id]) }}">
                            <i class="bi bi-file-earmark-text me-2"></i>Cotizaciones
                        </a>
                        <span class="small text-muted">{{ $historial->cotizaciones }} cotizacion(es)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('ventas.cobros.index', ['cliente_id' => $cliente->id]) }}">
                            <i class="bi bi-cash-coin me-2"></i>Cobros
                        </a>
                        <span class="small text-muted">${{ number_format((float) $historial->monto_cobrado, 2) }} cobrado</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('ventas.reportes.cuentas-por-cobrar', ['cliente_id' => $cliente->id]) }}">
                            <i class="bi bi-hourglass-split me-2"></i>Cuentas por cobrar
                        </a>
                        <span class="small text-muted">Saldo de ${{ number_format((float) $historial->saldo_pendiente, 2) }}</span>
                    </div>
                @else
                    <x-empty message="Todavia no hay historial comercial." icon="receipt" />
                @endif
            </x-card>
        </div>
    </div>
@endsection
