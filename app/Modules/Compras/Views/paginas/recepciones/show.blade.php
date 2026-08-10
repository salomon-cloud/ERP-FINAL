@extends('layouts.app')

@section('title', 'Recepcion')
@section('header', $recepcion->numero_recepcion)
@section('subtitle', 'Recepcion de mercancia')

@section('content')
    <x-page-header :title="$recepcion->numero_recepcion" subtitle="Compras / Recepciones / Detalle">
        @if ($recepcion->estado->esEditable())
            @can('compras.recepciones.aplicar')
                <form method="POST" action="{{ route('compras.recepciones.aplicar', $recepcion) }}"
                      data-confirm="Aplicar la recepcion? La mercancia entrara al inventario.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Aplicar</button>
                </form>
            @endcan
        @endif

        @if ($recepcion->estado->esCancelable())
            @can('compras.recepciones.aplicar')
                <form method="POST" action="{{ route('compras.recepciones.cancelar', $recepcion) }}"
                      data-confirm="Cancelar la recepcion? Si ya estaba aplicada, sus movimientos se revertiran.">
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
                        <div class="fs-4 fw-bold">{{ $recepcion->numero_recepcion }}</div>
                        <div class="text-muted">
                            Contra <a href="{{ route('compras.ordenes.show', $recepcion->ordenCompra) }}">{{ $recepcion->ordenCompra?->numero_orden }}</a>
                            · {{ $recepcion->ordenCompra?->proveedor?->nombre }}
                        </div>
                        <div class="small text-muted">
                            Entra a {{ $recepcion->almacen?->nombre }} · {{ $recepcion->fecha?->format('d/m/Y') }}
                        </div>
                    </div>
                    <x-badge :estado="$recepcion->estado->color()" :label="$recepcion->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', 'Ubicacion', 'Lote', ['label' => 'Recibido', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], '']">
                    @forelse ($recepcion->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="small">{{ $linea->ubicacion?->codigo ?? '--' }}</td>
                            <td class="small">{{ $linea->lote?->numero_lote ?? '--' }}</td>
                            <td class="text-end fw-bold">{{ (float) $linea->cantidad_recibida }}</td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="text-end">
                                @if ($recepcion->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('compras.recepciones.lineas.destroy', [$recepcion, $linea]) }}"
                                          data-confirm="Quitar este renglon?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="6" message="Captura lo que llego antes de aplicar." icon="box-arrow-in-down" />
                    @endforelse
                </x-table>

                @if ($recepcion->estado->esEditable())
                    @if ($pendientes->isEmpty())
                        <div class="alert alert-success small mt-3 mb-0">
                            Esta orden ya no tiene nada pendiente por recibir.
                        </div>
                    @else
                        <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                              action="{{ route('compras.recepciones.lineas.store', $recepcion) }}">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label" for="orden_compra_linea_id">Renglon pendiente</label>
                                <select class="form-select @error('orden_compra_linea_id') is-invalid @enderror"
                                        id="orden_compra_linea_id" name="orden_compra_linea_id" required>
                                    <option value="">Selecciona...</option>
                                    @foreach ($pendientes as $linea)
                                        <option value="{{ $linea->id }}">
                                            {{ $linea->producto?->nombre ?? $linea->descripcion }}
                                            (faltan {{ $linea->cantidad_pendiente + 0 }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('orden_compra_linea_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="cantidad_recibida">Cantidad</label>
                                <input class="form-control @error('cantidad_recibida') is-invalid @enderror" type="number"
                                       step="0.000001" min="0.000001" id="cantidad_recibida" name="cantidad_recibida" required>
                                @error('cantidad_recibida') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="ubicacion_id">Ubicacion</label>
                                <select class="form-select" id="ubicacion_id" name="ubicacion_id">
                                    <option value="">Sin definir</option>
                                    @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}">{{ $ubicacion->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="lote_id">Lote</label>
                                <select class="form-select" id="lote_id" name="lote_id">
                                    <option value="">Sin lote</option>
                                    @foreach ($lotes as $lote)
                                        <option value="{{ $lote->id }}">{{ $lote->numero_lote }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" type="submit">
                                    <i class="bi bi-plus-lg"></i> Agregar
                                </button>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">
                                    El costo lo toma de la orden. No se puede recibir mas de lo pedido.
                                </small>
                            </div>
                        </form>
                    @endif
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Seguimiento" class="mb-3">
                <dl class="row mb-0">
                    <dt class="col-6">Recibio</dt><dd class="col-6">{{ $recepcion->recibidoPor?->name ?? '--' }}</dd>
                    <dt class="col-6">Almacen</dt><dd class="col-6">{{ $recepcion->almacen?->nombre ?? '--' }}</dd>
                    <dt class="col-6">Fecha</dt><dd class="col-6">{{ $recepcion->fecha?->format('d/m/Y') }}</dd>
                </dl>

                @if ($recepcion->estado->esEditable())
                    <div class="alert alert-info small mt-3 mb-0">
                        Aplicar es lo que crea el inventario. Hasta entonces esta recepcion es solo una captura.
                    </div>
                @endif
            </x-card>

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
