@extends('layouts.app')

@section('title', 'Proveedor')
@section('header', $proveedor->nombre)
@section('subtitle', $proveedor->codigo)

@section('content')
    <x-page-header :title="$proveedor->nombre" :subtitle="'Compras / Proveedores / '.$proveedor->codigo">
        <a class="btn btn-outline-primary" href="{{ route('compras.ordenes.create', ['proveedor_id' => $proveedor->id]) }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva orden
        </a>
        <a class="btn btn-primary" href="{{ route('compras.proveedores.edit', $proveedor) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card title="Datos del proveedor">
                <dl class="row mb-0">
                    <dt class="col-5">Codigo</dt><dd class="col-7">{{ $proveedor->codigo }}</dd>
                    <dt class="col-5">Razon social</dt><dd class="col-7">{{ $proveedor->razon_social ?: '--' }}</dd>
                    <dt class="col-5">RFC</dt><dd class="col-7">{{ $proveedor->rfc ?: '--' }}</dd>
                    <dt class="col-5">Contacto</dt><dd class="col-7">{{ $proveedor->contacto ?: '--' }}</dd>
                    <dt class="col-5">Correo</dt><dd class="col-7">{{ $proveedor->correo ?: '--' }}</dd>
                    <dt class="col-5">Telefono</dt><dd class="col-7">{{ $proveedor->telefono ?: '--' }}</dd>
                    <dt class="col-5">Condicion</dt><dd class="col-7">{{ $proveedor->condicionPago?->nombre ?? '--' }}</dd>
                    <dt class="col-5">Moneda</dt><dd class="col-7">{{ $proveedor->moneda }}</dd>
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7"><x-badge :estado="$proveedor->estado->color()" :label="$proveedor->estado->label()" /></dd>
                    <dt class="col-5">Saldo</dt>
                    <dd class="col-7 fw-bold">${{ number_format($proveedor->saldo_pendiente, 2) }}</dd>
                    @if ($proveedor->direccion)
                        <dt class="col-5">Direccion</dt><dd class="col-7">{{ $proveedor->direccion }}</dd>
                    @endif
                </dl>
            </x-card>
        </div>

        <div class="col-lg-8">
            <x-card title="Ultimas ordenes de compra" class="mb-3">
                <x-table :head="['Folio', 'Fecha', ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
                    @forelse ($ordenes as $orden)
                        <tr>
                            <td class="fw-bold">{{ $orden->numero_orden }}</td>
                            <td class="small">{{ $orden->fecha?->format('d/m/Y') }}</td>
                            <td class="text-end">${{ number_format((float) $orden->total, 2) }}</td>
                            <td><x-badge :estado="$orden->estado->color()" :label="$orden->estado->label()" /></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.ordenes.show', $orden) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="Todavia no se le ha comprado nada." icon="file-earmark-text" />
                    @endforelse
                </x-table>
            </x-card>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-card title="Facturas">
                        <x-table :head="['Folio', ['label' => 'Saldo', 'align' => 'end'], 'Estado']">
                            @forelse ($facturas as $factura)
                                <tr>
                                    <td><a href="{{ route('compras.facturas.show', $factura) }}">{{ $factura->numero_factura }}</a></td>
                                    <td class="text-end">${{ number_format($factura->saldo, 2) }}</td>
                                    <td><x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" /></td>
                                </tr>
                            @empty
                                <x-empty :colspan="3" message="Sin facturas." icon="receipt" />
                            @endforelse
                        </x-table>
                    </x-card>
                </div>
                <div class="col-md-6">
                    <x-card title="Pagos">
                        <x-table :head="['Folio', ['label' => 'Monto', 'align' => 'end'], 'Estado']">
                            @forelse ($pagos as $pago)
                                <tr>
                                    <td><a href="{{ route('compras.pagos.show', $pago) }}">{{ $pago->numero_pago }}</a></td>
                                    <td class="text-end">${{ number_format((float) $pago->monto, 2) }}</td>
                                    <td><x-badge :estado="$pago->estado->color()" :label="$pago->estado->label()" /></td>
                                </tr>
                            @empty
                                <x-empty :colspan="3" message="Sin pagos." icon="cash-stack" />
                            @endforelse
                        </x-table>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
@endsection
