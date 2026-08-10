@extends('layouts.app')

@section('title', 'Pago a proveedor')
@section('header', $pago->numero_pago)

@section('content')
    <x-page-header :title="$pago->numero_pago" subtitle="Compras / Pagos / Detalle">
        @if ($pago->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('compras.pagos.edit', $pago) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @can('compras.pagos.aplicar')
                <form method="POST" action="{{ route('compras.pagos.aplicar', $pago) }}"
                      data-confirm="Aplicar el pago? Bajara el saldo de la factura.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Aplicar</button>
                </form>
            @endcan
        @endif

        @if ($pago->estado !== \App\Modules\Compras\Enums\EstadoPago::Cancelado)
            @can('compras.pagos.aplicar')
                <form method="POST" action="{{ route('compras.pagos.cancelar', $pago) }}"
                      data-confirm="Cancelar el pago? Si estaba aplicado, el saldo volvera a la factura.">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $pago->numero_pago }}</div>
                        <div class="text-muted">{{ $pago->proveedor?->nombre ?? '--' }}</div>
                    </div>
                    <div class="text-end">
                        <x-badge :estado="$pago->estado->color()" :label="$pago->estado->label()" class="fs-6" />
                        <div class="fs-3 fw-bold mt-2">${{ number_format((float) $pago->monto, 2) }}</div>
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Fecha</dt><dd class="col-sm-8">{{ $pago->fecha?->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Forma de pago</dt><dd class="col-sm-8">{{ $pago->forma_pago->label() }}</dd>
                    <dt class="col-sm-4">Referencia</dt><dd class="col-sm-8">{{ $pago->referencia ?: '--' }}</dd>
                    <dt class="col-sm-4">Factura</dt>
                    <dd class="col-sm-8">
                        @if ($pago->factura)
                            <a href="{{ route('compras.facturas.show', $pago->factura) }}">{{ $pago->factura->numero_factura }}</a>
                            <span class="text-muted small">
                                (total ${{ number_format((float) $pago->factura->total, 2) }},
                                saldo ${{ number_format($pago->factura->saldo, 2) }})
                            </span>
                        @else
                            Pago a cuenta, sin factura asignada
                        @endif
                    </dd>
                </dl>

                @if ($pago->estado->esEditable())
                    <div class="alert alert-info small mt-3 mb-0">
                        Un pago en borrador todavia no toca el saldo de la factura. Aplicalo cuando el dinero
                        salga del banco.
                    </div>
                @endif
            </x-card>
        </div>

        <div class="col-lg-5">
            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
