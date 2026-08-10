@extends('layouts.app')

@section('title', 'Factura de proveedor')
@section('header', $factura->numero_factura)
@section('subtitle', 'Factura de proveedor')

@section('content')
    <x-page-header :title="$factura->numero_factura" subtitle="Compras / Facturas / Detalle">
        @if ($factura->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('compras.facturas.edit', $factura) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @can('compras.facturas.contabilizar')
                <form method="POST" action="{{ route('compras.facturas.contabilizar', $factura) }}"
                      data-confirm="Contabilizar la factura? Se hara el cotejo de tres vias.">
                    @csrf
                    <input type="hidden" name="version_fila" value="{{ $factura->version_fila }}">
                    <button class="btn btn-primary" @disabled($diferencias !== [])>
                        <i class="bi bi-journal-check me-1"></i>Contabilizar
                    </button>
                </form>
            @endcan
        @endif

        @if ($factura->estado->admitePago())
            <a class="btn btn-primary" href="{{ route('compras.pagos.create', ['factura_proveedor_id' => $factura->id]) }}">
                <i class="bi bi-cash-coin me-1"></i>Registrar pago
            </a>
            <a class="btn btn-outline-primary" href="{{ route('compras.devoluciones.create', ['factura_proveedor_id' => $factura->id]) }}">
                <i class="bi bi-arrow-return-left me-1"></i>Devolver
            </a>
        @endif

        @if ($factura->estado->esCancelable())
            @can('compras.facturas.contabilizar')
                <form method="POST" action="{{ route('compras.facturas.cancelar', $factura) }}"
                      data-confirm="Cancelar la factura {{ $factura->numero_factura }}?">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    @if ($diferencias !== [])
        <div class="alert alert-warning">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>El cotejo de tres vias no cuadra</div>
            <ul class="mb-0 small">
                @foreach ($diferencias as $diferencia)
                    <li>{{ $diferencia }}</li>
                @endforeach
            </ul>
            <div class="small mt-2">
                Se compara lo que se PIDIO (orden), lo que LLEGO (recepciones) y lo que se COBRA (esta factura).
                Corrige las lineas o recibe lo que falte antes de contabilizar.
            </div>
        </div>
    @elseif ($factura->orden_compra_id !== null && $factura->estado->esEditable())
        <div class="alert alert-success small">
            <i class="bi bi-check2-circle me-1"></i>El cotejo de tres vias cuadra: lo facturado no excede lo recibido.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $factura->numero_factura }}</div>
                        <div class="text-muted">{{ $factura->proveedor?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            Fecha {{ $factura->fecha?->format('d/m/Y') }}
                            @if ($factura->fecha_vencimiento) · Vence {{ $factura->fecha_vencimiento->format('d/m/Y') }} @endif
                            @if ($factura->ordenCompra)
                                · <a href="{{ route('compras.ordenes.show', $factura->ordenCompra) }}">{{ $factura->ordenCompra->numero_orden }}</a>
                            @endif
                        </div>
                    </div>
                    <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', 'Renglon de la orden', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($factura->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? $linea->descripcion }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="small text-muted">
                                @if ($linea->ordenCompraLinea)
                                    Recibido: {{ (float) $linea->ordenCompraLinea->cantidad_recibida }}
                                @else
                                    <span class="text-warning">Sin referencia</span>
                                @endif
                            </td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_impuesto, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end">
                                @if ($factura->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('compras.facturas.lineas.destroy', [$factura, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="7" message="Captura o copia las lineas antes de contabilizar." icon="receipt" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 280px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $factura->total, 2) }}</dd>
                        <dt class="col-6 text-end">Pagado</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $factura->total_pagado, 2) }}</dd>
                        <dt class="col-6 text-end">Saldo</dt>
                        <dd class="col-6 text-end fw-bold">${{ number_format($factura->saldo, 2) }}</dd>
                    </dl>
                </div>

                @if ($factura->estado->esEditable())
                    @if ($factura->orden_compra_id !== null && $factura->lineas->isEmpty())
                        <form class="mt-3 no-print" method="POST" action="{{ route('compras.facturas.copiar-orden', $factura) }}">
                            @csrf
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-clipboard-check me-1"></i>Copiar lo recibido de la orden
                            </button>
                            <small class="text-muted d-block mt-1">
                                Trae los renglones con su cantidad ya recibida, que es lo que el proveedor deberia estar cobrando.
                            </small>
                        </form>
                    @endif

                    <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('compras.facturas.lineas.store', $factura) }}">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="orden_compra_linea_id">Renglon de la orden</label>
                            <select class="form-select" id="orden_compra_linea_id" name="orden_compra_linea_id">
                                <option value="">Sin referencia</option>
                                @foreach ($factura->ordenCompra?->lineas ?? [] as $ordenLinea)
                                    <option value="{{ $ordenLinea->id }}">
                                        {{ $ordenLinea->producto?->nombre ?? $ordenLinea->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="producto_id">Producto</label>
                            <select class="form-select" id="producto_id" name="producto_id">
                                <option value="">Sin producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="cantidad">Cantidad</label>
                            <input class="form-control @error('cantidad') is-invalid @enderror" type="number"
                                   step="0.000001" min="0.000001" id="cantidad" name="cantidad" required>
                            @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="costo_unitario">Costo</label>
                            <input class="form-control @error('costo_unitario') is-invalid @enderror" type="number"
                                   step="0.01" min="0" id="costo_unitario" name="costo_unitario" required>
                            @error('costo_unitario') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

        <div class="col-lg-4">
            @if ($factura->pagos->isNotEmpty())
                <x-card title="Pagos" class="mb-3">
                    @foreach ($factura->pagos as $pago)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('compras.pagos.show', $pago) }}">{{ $pago->numero_pago }}</a>
                            <span>
                                ${{ number_format((float) $pago->monto, 2) }}
                                <x-badge :estado="$pago->estado->color()" :label="$pago->estado->label()" />
                            </span>
                        </div>
                    @endforeach
                </x-card>
            @endif

            @if ($factura->devoluciones->isNotEmpty())
                <x-card title="Devoluciones" class="mb-3">
                    @foreach ($factura->devoluciones as $devolucion)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('compras.devoluciones.show', $devolucion) }}">{{ $devolucion->numero_devolucion }}</a>
                            <x-badge :estado="$devolucion->estado->color()" :label="$devolucion->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
