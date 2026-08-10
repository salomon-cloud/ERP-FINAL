@extends('layouts.app')

@section('title', 'Orden de compra')
@section('header', $orden->numero_orden)
@section('subtitle', 'Orden de compra')

@section('content')
    <x-page-header :title="$orden->numero_orden" subtitle="Compras / Ordenes / Detalle">
        @if ($orden->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('compras.ordenes.edit', $orden) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST" action="{{ route('compras.ordenes.enviar', $orden) }}"
                  data-confirm="Marcar la orden como enviada al proveedor?">
                @csrf
                <button class="btn btn-outline-primary"><i class="bi bi-send me-1"></i>Marcar enviada</button>
            </form>
        @endif

        @if ($orden->estado->esEditable())
            @can('compras.ordenes.confirmar')
                {{-- version_fila viaja en el formulario: es el bloqueo optimista.
                     Si alguien mas movio la orden mientras se revisaba, el
                     servicio aborta en vez de autorizar numeros viejos. --}}
                <form method="POST" action="{{ route('compras.ordenes.confirmar', $orden) }}"
                      data-confirm="Confirmar la orden? A partir de ahi se puede recibir mercancia contra ella.">
                    @csrf
                    <input type="hidden" name="version_fila" value="{{ $orden->version_fila }}">
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Confirmar</button>
                </form>
            @endcan
        @endif

        @if ($orden->estado->admiteRecepcion())
            <a class="btn btn-primary" href="{{ route('compras.recepciones.create', ['orden_compra_id' => $orden->id]) }}">
                <i class="bi bi-box-arrow-in-down me-1"></i>Registrar recepcion
            </a>
        @endif

        @if ($orden->estado->admiteFactura())
            <a class="btn btn-outline-primary"
               href="{{ route('compras.facturas.create', ['orden_compra_id' => $orden->id, 'proveedor_id' => $orden->proveedor_id]) }}">
                <i class="bi bi-receipt me-1"></i>Capturar factura
            </a>
        @endif

        @if ($orden->estado->esCancelable())
            @can('compras.ordenes.cancelar')
                <form method="POST" action="{{ route('compras.ordenes.cancelar', $orden) }}"
                      data-confirm="Cancelar la orden {{ $orden->numero_orden }}?">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $orden->numero_orden }}</div>
                        <div class="text-muted">{{ $orden->proveedor?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            Fecha {{ $orden->fecha?->format('d/m/Y') }}
                            @if ($orden->fecha_entrega) · Entrega {{ $orden->fecha_entrega->format('d/m/Y') }} @endif
                            @if ($orden->requisicion)
                                · Desde <a href="{{ route('compras.requisiciones.show', $orden->requisicion) }}">{{ $orden->requisicion->numero_requisicion }}</a>
                            @endif
                        </div>
                    </div>
                    <x-badge :estado="$orden->estado->color()" :label="$orden->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', 'Almacen', ['label' => 'Pedido', 'align' => 'end'], ['label' => 'Recibido', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], ['label' => 'Desc.', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($orden->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? $linea->descripcion }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="small">{{ $linea->almacen?->codigo ?? '--' }}</td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end {{ $linea->cantidad_pendiente > 0 ? 'text-warning fw-bold' : 'text-success fw-bold' }}">
                                {{ (float) $linea->cantidad_recibida }}
                            </td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_descuento, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_impuesto, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end">
                                @if ($orden->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('compras.ordenes.lineas.destroy', [$orden, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="9" message="Agrega al menos una linea antes de confirmar." icon="file-earmark-text" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 280px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $orden->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Descuentos</dt>
                        <dd class="col-6 text-end">-${{ number_format((float) $orden->total_descuento, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $orden->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $orden->total, 2) }}</dd>
                    </dl>
                </div>

                @if ($orden->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('compras.ordenes.lineas.store', $orden) }}">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="producto_id">Producto</label>
                            <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}"
                                            data-costo="{{ (float) $producto->costo }}"
                                            data-impuesto="{{ $producto->impuesto_id }}">{{ $producto->etiqueta }}</option>
                                @endforeach
                            </select>
                            @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="almacen_id">Almacen</label>
                            <select class="form-select" id="almacen_id" name="almacen_id">
                                <option value="">Sin definir</option>
                                @foreach ($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}">{{ $almacen->codigo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label" for="cantidad">Cant.</label>
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
                            <label class="form-label" for="porcentaje_descuento">Desc. %</label>
                            <input class="form-control" type="number" step="0.01" min="0" max="100"
                                   id="porcentaje_descuento" name="porcentaje_descuento" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="impuesto_id">Impuesto</label>
                            <select class="form-select" id="impuesto_id" name="impuesto_id">
                                <option value="">Sin impuesto</option>
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
                        <div class="col-12">
                            <small class="text-muted">
                                El costo y la tasa se congelan al agregar la linea: cambiar despues el catalogo no
                                reescribe una orden ya enviada.
                            </small>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($orden->recepciones->isNotEmpty())
                <x-card title="Recepciones" class="mb-3">
                    @foreach ($orden->recepciones as $recepcion)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('compras.recepciones.show', $recepcion) }}">{{ $recepcion->numero_recepcion }}</a>
                            <x-badge :estado="$recepcion->estado->color()" :label="$recepcion->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @if ($orden->facturas->isNotEmpty())
                <x-card title="Facturas" class="mb-3">
                    @foreach ($orden->facturas as $factura)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('compras.facturas.show', $factura) }}">{{ $factura->numero_factura }}</a>
                            <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
