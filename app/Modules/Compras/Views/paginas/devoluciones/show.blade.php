@extends('layouts.app')

@section('title', 'Devolucion a proveedor')
@section('header', $devolucion->numero_devolucion)
@section('subtitle', 'Devolucion a proveedor')

@section('content')
    <x-page-header :title="$devolucion->numero_devolucion" subtitle="Compras / Devoluciones / Detalle">
        @if ($devolucion->estado->esEditable())
            @can('compras.facturas.contabilizar')
                <form method="POST" action="{{ route('compras.devoluciones.aplicar', $devolucion) }}"
                      data-confirm="Aplicar la devolucion? La mercancia saldra del almacen.">
                    @csrf
                    <button class="btn btn-primary" @disabled($almacen === null)>
                        <i class="bi bi-check2-circle me-1"></i>Aplicar
                    </button>
                </form>
            @endcan
        @endif

        @if ($devolucion->estado->esCancelable())
            @can('compras.facturas.contabilizar')
                <form method="POST" action="{{ route('compras.devoluciones.cancelar', $devolucion) }}"
                      data-confirm="Cancelar la devolucion? Si ya estaba aplicada, la mercancia volvera al almacen.">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    @if ($almacen === null)
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            No se puede saber de que almacen sale la mercancia: la factura no tiene una recepcion aplicada.
            Aplica primero la recepcion de su orden de compra.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $devolucion->numero_devolucion }}</div>
                        <div class="text-muted">{{ $devolucion->proveedor?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            {{ $devolucion->motivo->label() }} · {{ $devolucion->fecha?->format('d/m/Y') }}
                            @if ($devolucion->factura)
                                · <a href="{{ route('compras.facturas.show', $devolucion->factura) }}">{{ $devolucion->factura->numero_factura }}</a>
                            @endif
                            @if ($almacen) · Sale de {{ $almacen->nombre }} @endif
                        </div>
                    </div>
                    <x-badge :estado="$devolucion->estado->color()" :label="$devolucion->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($devolucion->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_impuesto, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end">
                                @if ($devolucion->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('compras.devoluciones.lineas.destroy', [$devolucion, $linea]) }}"
                                          data-confirm="Quitar este renglon?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="6" message="Elige que se regresa antes de aplicar." icon="arrow-return-left" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 260px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $devolucion->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $devolucion->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $devolucion->total, 2) }}</dd>
                    </dl>
                </div>

                @if ($devolucion->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('compras.devoluciones.lineas.store', $devolucion) }}">
                        @csrf
                        <div class="col-md-7">
                            <label class="form-label" for="factura_proveedor_linea_id">Renglon de la factura</label>
                            <select class="form-select @error('factura_proveedor_linea_id') is-invalid @enderror"
                                    id="factura_proveedor_linea_id" name="factura_proveedor_linea_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($lineasFactura as $facturaLinea)
                                    <option value="{{ $facturaLinea->id }}">
                                        {{ $facturaLinea->producto?->nombre ?? $facturaLinea->descripcion }}
                                        (facturado {{ (float) $facturaLinea->cantidad }})
                                    </option>
                                @endforeach
                            </select>
                            @error('factura_proveedor_linea_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cantidad">Cantidad a devolver</label>
                            <input class="form-control @error('cantidad') is-invalid @enderror" type="number"
                                   step="0.000001" min="0.000001" id="cantidad" name="cantidad" required>
                            @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">El costo se toma de la factura: se reclama lo que se pago.</small>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
