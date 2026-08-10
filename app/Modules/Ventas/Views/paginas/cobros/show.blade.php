@extends('layouts.app')

@section('title', 'Cobro')
@section('header', $cobro->numero_cobro)

@section('content')
    <x-page-header :title="$cobro->numero_cobro" subtitle="Ventas / Cobros / Detalle">
        @if ($cobro->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('ventas.cobros.edit', $cobro) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @can('ventas.cobros.aplicar')
                <form method="POST" action="{{ route('ventas.cobros.aplicar', $cobro) }}"
                      data-confirm="Aplicar el cobro? Bajara el saldo de la factura.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Aplicar</button>
                </form>
            @endcan
        @endif

        @if ($cobro->estado !== \App\Modules\Ventas\Enums\EstadoCobro::Cancelado)
            @can('ventas.cobros.aplicar')
                <form method="POST" action="{{ route('ventas.cobros.cancelar', $cobro) }}"
                      data-confirm="Cancelar el cobro? Si estaba aplicado, el saldo volvera a la factura.">
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
                        <div class="fs-4 fw-bold">{{ $cobro->numero_cobro }}</div>
                        <div class="text-muted">{{ $cobro->cliente?->nombre ?? '--' }}</div>
                    </div>
                    <div class="text-end">
                        <x-badge :estado="$cobro->estado->color()" :label="$cobro->estado->label()" class="fs-6" />
                        <div class="fs-3 fw-bold mt-2">${{ number_format((float) $cobro->monto, 2) }}</div>
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Fecha</dt><dd class="col-sm-8">{{ $cobro->fecha?->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Forma de pago</dt><dd class="col-sm-8">{{ $cobro->forma_pago->label() }}</dd>
                    <dt class="col-sm-4">Referencia</dt><dd class="col-sm-8">{{ $cobro->referencia ?: '--' }}</dd>
                    <dt class="col-sm-4">Factura</dt>
                    <dd class="col-sm-8">
                        @if ($cobro->factura)
                            <a href="{{ route('ventas.facturas.show', $cobro->factura) }}">{{ $cobro->factura->numero_factura }}</a>
                            <span class="text-muted small">
                                (total ${{ number_format((float) $cobro->factura->total, 2) }},
                                saldo ${{ number_format($cobro->factura->saldo, 2) }})
                            </span>
                        @else
                            Cobro a cuenta, sin factura asignada
                        @endif
                    </dd>
                </dl>

                @if ($cobro->estado->esEditable())
                    <div class="alert alert-info small mt-3 mb-0">
                        Un cobro en borrador todavia no toca el saldo de la factura. Aplicalo cuando el dinero
                        entre de verdad.
                    </div>
                @endif
            </x-card>
        </div>

        <div class="col-lg-5">
            @if ($cobro->estado->esEditable() && $cobro->factura_id === null && $facturasDelCliente->isNotEmpty())
                <x-card title="Asignar a una factura" subtitle="Este cobro entro a cuenta" class="mb-3">
                    <form method="POST" action="{{ route('ventas.cobros.asignar-factura', $cobro) }}">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label" for="factura_id">Factura del cliente</label>
                            <select class="form-select" id="factura_id" name="factura_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($facturasDelCliente as $factura)
                                    <option value="{{ $factura->id }}">
                                        {{ $factura->numero_factura }} (saldo ${{ number_format($factura->saldo, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-outline-primary w-100" type="submit">
                            <i class="bi bi-link-45deg me-1"></i>Asignar
                        </button>
                    </form>
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
