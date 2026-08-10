@extends('layouts.app')

@section('title', 'Nota de credito')
@section('header', $nota->numero_nota)
@section('subtitle', 'Nota de credito')

@section('content')
    <x-page-header :title="$nota->numero_nota" subtitle="Ventas / Notas de credito / Detalle">
        @if ($nota->estado->esCancelable())
            @can('ventas.notas_credito.emitir')
                <form method="POST" action="{{ route('ventas.notas-credito.cancelar', $nota) }}"
                      data-confirm="Cancelar la nota? Si ya estaba emitida con devolucion, la mercancia volvera a salir.">
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
                        <div class="fs-4 fw-bold">{{ $nota->numero_nota }}</div>
                        <div class="text-muted">{{ $nota->cliente?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            {{ $nota->motivo->label() }} · {{ $nota->fecha_emision?->format('d/m/Y') }}
                            @if ($nota->factura)
                                · <a href="{{ route('ventas.facturas.show', $nota->factura) }}">{{ $nota->factura->numero_factura }}</a>
                            @endif
                        </div>
                    </div>
                    <x-badge :estado="$nota->estado->color()" :label="$nota->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($nota->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? $linea->descripcion }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end">${{ number_format((float) $linea->precio_unitario, 2) }}</td>
                            <td class="text-end">${{ number_format((float) $linea->monto_impuesto, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end">
                                @if ($nota->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('ventas.notas-credito.lineas.destroy', [$nota, $linea]) }}"
                                          data-confirm="Quitar este renglon?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="6" message="Elige que se acredita antes de emitir." icon="arrow-return-left" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 260px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $nota->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $nota->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $nota->total, 2) }}</dd>
                    </dl>
                </div>

                @if ($nota->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('ventas.notas-credito.lineas.store', $nota) }}">
                        @csrf
                        <div class="col-md-7">
                            <label class="form-label" for="factura_linea_id">Renglon de la factura</label>
                            <select class="form-select @error('factura_linea_id') is-invalid @enderror"
                                    id="factura_linea_id" name="factura_linea_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($lineasFactura as $facturaLinea)
                                    <option value="{{ $facturaLinea->id }}">
                                        {{ $facturaLinea->producto?->nombre ?? $facturaLinea->descripcion }}
                                        (facturado {{ (float) $facturaLinea->cantidad }})
                                    </option>
                                @endforeach
                            </select>
                            @error('factura_linea_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cantidad">Cantidad a acreditar</label>
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
                            <small class="text-muted">El precio se toma de la factura: se acredita lo que se cobro.</small>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($nota->estado->esEditable())
                <x-card title="Emitir la nota" class="mb-3">
                    <form method="POST" action="{{ route('ventas.notas-credito.emitir', $nota) }}"
                          data-confirm="Emitir la nota de credito?">
                        @csrf

                        @if ($nota->motivo->regresaMercancia())
                            <div class="mb-3">
                                <label class="form-label" for="almacen_id">Almacen que recibe la devolucion</label>
                                <select class="form-select" id="almacen_id" name="almacen_id">
                                    <option value="">El del pedido original</option>
                                    @foreach ($almacenes as $almacen)
                                        <option value="{{ $almacen->id }}">{{ $almacen->codigo }} - {{ $almacen->nombre }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Esta nota SI regresa mercancia al inventario, porque su motivo es una devolucion.
                                </small>
                            </div>
                        @else
                            <p class="small text-muted">
                                Esta nota acredita dinero pero NO mueve inventario: su motivo no es una devolucion
                                de mercancia.
                            </p>
                        @endif

                        <button class="btn btn-primary w-100" type="submit" @disabled($nota->lineas->isEmpty())>
                            <i class="bi bi-send-check me-1"></i>Emitir
                        </button>
                    </form>
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
