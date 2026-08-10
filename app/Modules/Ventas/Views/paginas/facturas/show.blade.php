@extends('layouts.app')

@section('title', 'Factura')
@section('header', $factura->numero_factura)
@section('subtitle', 'Factura de venta')

@section('content')
    <x-page-header :title="$factura->numero_factura" subtitle="Ventas / Facturas / Detalle">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>

        @if ($factura->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('ventas.facturas.edit', $factura) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @can('ventas.facturas.emitir')
                <form method="POST" action="{{ route('ventas.facturas.emitir', $factura) }}"
                      data-confirm="Emitir la factura? Se contabiliza y a partir de ahi solo se corrige con una nota de credito.">
                    @csrf
                    <input type="hidden" name="version_fila" value="{{ $factura->version_fila }}">
                    <button class="btn btn-primary"><i class="bi bi-send-check me-1"></i>Emitir</button>
                </form>
            @endcan
        @endif

        @if ($factura->estado->admiteCobro())
            <a class="btn btn-primary" href="{{ route('ventas.cobros.create', ['factura_id' => $factura->id]) }}">
                <i class="bi bi-cash-coin me-1"></i>Registrar cobro
            </a>
        @endif

        @if ($factura->estado->admiteNotaCredito())
            <a class="btn btn-outline-primary" href="{{ route('ventas.notas-credito.create', ['factura_id' => $factura->id]) }}">
                <i class="bi bi-arrow-return-left me-1"></i>Nota de credito
            </a>
        @endif

        @if ($factura->estado->esCancelable())
            @can('ventas.facturas.cancelar')
                <form method="POST" action="{{ route('ventas.facturas.cancelar', $factura) }}"
                      data-confirm="Cancelar la factura {{ $factura->numero_factura }}?">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    @if ($factura->esta_vencida)
        <div class="alert alert-warning small no-print">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Esta factura vencio el {{ $factura->fecha_vencimiento->format('d/m/Y') }} y sigue con saldo de
            ${{ number_format($factura->saldo, 2) }}.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $factura->numero_factura }}</div>
                        <div class="text-muted">{{ $factura->cliente?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            {{ $factura->cliente?->rfc ? 'RFC '.$factura->cliente->rfc : 'Publico en general' }}
                        </div>
                        <div class="small text-muted">
                            Emision {{ $factura->fecha_emision?->format('d/m/Y') }}
                            @if ($factura->fecha_vencimiento) · Vence {{ $factura->fecha_vencimiento->format('d/m/Y') }} @endif
                            @if ($factura->condicionPago) · {{ $factura->condicionPago->nombre }} @endif
                            @if ($factura->pedido)
                                · <a href="{{ route('ventas.pedidos.show', $factura->pedido) }}">{{ $factura->pedido->numero_pedido }}</a>
                            @endif
                        </div>
                    </div>
                    <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Desc.', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($factura->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? $linea->descripcion }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end">${{ number_format((float) $linea->precio_unitario, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_descuento, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_impuesto, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end no-print">
                                @if ($factura->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('ventas.facturas.lineas.destroy', [$factura, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="7" message="Captura las lineas antes de emitir." icon="receipt" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 300px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Descuentos</dt>
                        <dd class="col-6 text-end">-${{ number_format((float) $factura->total_descuento, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $factura->total, 2) }}</dd>
                        <dt class="col-6 text-end">Cobrado</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->total_cobrado, 2) }}</dd>
                        @if ($factura->total_acreditado > 0)
                            <dt class="col-6 text-end">Acreditado</dt>
                            <dd class="col-6 text-end">-${{ number_format($factura->total_acreditado, 2) }}</dd>
                        @endif
                        <dt class="col-6 text-end">Saldo</dt>
                        <dd class="col-6 text-end fw-bold">${{ number_format($factura->saldo, 2) }}</dd>
                    </dl>
                </div>

                @if ($factura->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('ventas.facturas.lineas.store', $factura) }}">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="producto_id">Producto</label>
                            <select class="form-select" id="producto_id" name="producto_id">
                                <option value="">Concepto libre</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="descripcion">Descripcion</label>
                            <input class="form-control" type="text" id="descripcion" name="descripcion" maxlength="300">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label" for="cantidad">Cant.</label>
                            <input class="form-control @error('cantidad') is-invalid @enderror" type="number"
                                   step="0.000001" min="0.000001" id="cantidad" name="cantidad" required>
                            @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="precio_unitario">Precio</label>
                            <input class="form-control @error('precio_unitario') is-invalid @enderror" type="number"
                                   step="0.01" min="0" id="precio_unitario" name="precio_unitario" required>
                            @error('precio_unitario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-1">
                            <label class="form-label" for="impuesto_id">Imp.</label>
                            <select class="form-select" id="impuesto_id" name="impuesto_id">
                                <option value="">--</option>
                                @foreach ($impuestos as $impuesto)
                                    <option value="{{ $impuesto->id }}">{{ $impuesto->codigo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-outline-primary w-100" type="submit" title="Agregar linea">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4 no-print">
            @if ($factura->cobros->isNotEmpty())
                <x-card title="Cobros" class="mb-3">
                    @foreach ($factura->cobros as $cobro)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('ventas.cobros.show', $cobro) }}">{{ $cobro->numero_cobro }}</a>
                            <span>
                                ${{ number_format((float) $cobro->monto, 2) }}
                                <x-badge :estado="$cobro->estado->color()" :label="$cobro->estado->label()" />
                            </span>
                        </div>
                    @endforeach
                </x-card>
            @endif

            @if ($factura->notasCredito->isNotEmpty())
                <x-card title="Notas de credito" class="mb-3">
                    @foreach ($factura->notasCredito as $nota)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('ventas.notas-credito.show', $nota) }}">{{ $nota->numero_nota }}</a>
                            <span>
                                ${{ number_format((float) $nota->total, 2) }}
                                <x-badge :estado="$nota->estado->color()" :label="$nota->estado->label()" />
                            </span>
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
