@extends('layouts.app')

@section('title', 'Requisicion')
@section('header', $requisicion->numero_requisicion)
@section('subtitle', 'Requisicion de compra')

@section('content')
    <x-page-header :title="$requisicion->numero_requisicion" subtitle="Compras / Requisiciones / Detalle">
        @if ($requisicion->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('compras.requisiciones.edit', $requisicion) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST" action="{{ route('compras.requisiciones.enviar', $requisicion) }}"
                  data-confirm="Enviar la requisicion a revision?">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Enviar a revision</button>
            </form>
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $requisicion->numero_requisicion }}</div>
                        <div class="text-muted">
                            {{ $requisicion->departamento?->nombre ?? 'Sin departamento' }}
                            · Solicita {{ $requisicion->solicitante?->name ?? '--' }}
                        </div>
                        @if ($requisicion->fecha_requerida)
                            <div class="small text-muted">Requerida para el {{ $requisicion->fecha_requerida->format('d/m/Y') }}</div>
                        @endif
                    </div>
                    <x-badge :estado="$requisicion->estado->color()" :label="$requisicion->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', ['label' => 'Cantidad', 'align' => 'end'], 'Proveedor sugerido', 'Notas', '']">
                    @forelse ($requisicion->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="text-end fw-bold">{{ (float) $linea->cantidad_solicitada }}</td>
                            <td class="small">{{ $linea->proveedorSugerido?->nombre ?? '--' }}</td>
                            <td class="small text-muted">{{ $linea->notas ?: '--' }}</td>
                            <td class="text-end">
                                @if ($requisicion->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('compras.requisiciones.lineas.destroy', [$requisicion, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="Agrega lo que se necesita antes de enviar." icon="clipboard-plus" />
                    @endforelse
                </x-table>

                @if ($requisicion->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 no-print" method="POST"
                          action="{{ route('compras.requisiciones.lineas.store', $requisicion) }}">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="producto_id">Producto</label>
                            <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                                @endforeach
                            </select>
                            @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="cantidad_solicitada">Cantidad</label>
                            <input class="form-control @error('cantidad_solicitada') is-invalid @enderror" type="number"
                                   step="0.000001" min="0.000001" id="cantidad_solicitada" name="cantidad_solicitada" required>
                            @error('cantidad_solicitada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="proveedor_sugerido_id">Proveedor sugerido</label>
                            <select class="form-select" id="proveedor_sugerido_id" name="proveedor_sugerido_id">
                                <option value="">Ninguno</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($requisicion->estado->esRevisable())
                @can('compras.requisiciones.aprobar')
                    <x-card title="Autorizacion" subtitle="Aprobar deja convertirla en orden de compra" class="mb-3">
                        <form method="POST" action="{{ route('compras.requisiciones.revisar', $requisicion) }}">
                            @csrf @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label" for="comentario">Comentario</label>
                                <textarea class="form-control @error('comentario') is-invalid @enderror"
                                          id="comentario" name="comentario" rows="3" maxlength="500">{{ old('comentario') }}</textarea>
                                @error('comentario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit" name="decision" value="aprobar">
                                    <i class="bi bi-check-lg me-1"></i>Aprobar
                                </button>
                                <button class="btn btn-outline-danger" type="submit" name="decision" value="rechazar">
                                    <i class="bi bi-x-lg me-1"></i>Rechazar
                                </button>
                            </div>
                        </form>
                    </x-card>
                @endcan
            @endif

            @if ($requisicion->estado->esConvertible())
                <x-card title="Convertir en orden de compra" subtitle="Elige a quien se le va a comprar" class="mb-3">
                    <form method="POST" action="{{ route('compras.requisiciones.convertir', $requisicion) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="proveedor_id">Proveedor</label>
                            <select class="form-select @error('proveedor_id') is-invalid @enderror" id="proveedor_id" name="proveedor_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->etiqueta }}</option>
                                @endforeach
                            </select>
                            @error('proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-arrow-right-circle me-1"></i>Generar orden de compra
                        </button>
                        <small class="text-muted d-block mt-2">
                            Los costos se copian del catalogo. Revisalos en la orden antes de confirmarla.
                        </small>
                    </form>
                </x-card>
            @endif

            @if ($requisicion->ordenes->isNotEmpty())
                <x-card title="Ordenes generadas" class="mb-3">
                    @foreach ($requisicion->ordenes as $orden)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('compras.ordenes.show', $orden) }}">{{ $orden->numero_orden }}</a>
                            <x-badge :estado="$orden->estado->color()" :label="$orden->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
