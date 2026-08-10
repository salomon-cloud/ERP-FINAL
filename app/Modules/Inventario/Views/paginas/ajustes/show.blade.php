@extends('layouts.app')

@section('title', 'Ajuste de inventario')
@section('header', $ajuste->numero_ajuste)
@section('subtitle', 'Ajuste de inventario')

@section('content')
    <x-page-header :title="$ajuste->numero_ajuste" subtitle="Inventario / Ajustes / Detalle">
        @if ($ajuste->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('inventario.ajustes.edit', $ajuste) }}">
                <i class="bi bi-pencil me-1"></i>Editar motivo
            </a>
            @can('inventario.ajustes.aprobar')
                <form method="POST" action="{{ route('inventario.ajustes.aplicar', $ajuste) }}"
                      data-confirm="Aplicar el ajuste? Va a mover el inventario y no se puede deshacer sin otro ajuste.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Aplicar</button>
                </form>
            @endcan
            <form method="POST" action="{{ route('inventario.ajustes.cancelar', $ajuste) }}"
                  data-confirm="Cancelar el ajuste {{ $ajuste->numero_ajuste }}?">
                @csrf
                <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
            </form>
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $ajuste->numero_ajuste }}</div>
                        <div class="text-muted">{{ $ajuste->motivo }}</div>
                    </div>
                    <div class="text-end">
                        <x-badge :estado="$ajuste->estado->color()" :label="$ajuste->estado->label()" class="fs-6" />
                        <div class="mt-2 small text-muted">Impacto en el valor</div>
                        <div class="fs-5 fw-bold {{ $impacto < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $impacto > 0 ? '+' : '' }}${{ number_format($impacto, 2) }}
                        </div>
                    </div>
                </div>

                <x-table :head="['Producto', 'Almacen', 'Ubicacion', ['label' => 'Diferencia', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], 'Motivo de linea', '']">
                    @forelse ($ajuste->lineas as $linea)
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td>{{ $linea->almacen?->codigo ?? '--' }}</td>
                            <td>{{ $linea->ubicacion?->codigo ?? '--' }}</td>
                            <td class="text-end fw-bold {{ (float) $linea->diferencia < 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float) $linea->diferencia > 0 ? '+' : '' }}{{ (float) $linea->diferencia }}
                            </td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="small text-muted">{{ $linea->motivo ?: '--' }}</td>
                            <td class="text-end">
                                @if ($ajuste->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('inventario.ajustes.lineas.destroy', [$ajuste, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="7" message="Agrega al menos una linea antes de aplicar." icon="sliders" />
                    @endforelse
                </x-table>

                @if ($ajuste->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 no-print" method="POST"
                          action="{{ route('inventario.ajustes.lineas.store', $ajuste) }}">
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
                            <label class="form-label" for="almacen_id">Almacen</label>
                            <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id" required>
                                <option value="">...</option>
                                @foreach ($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}">{{ $almacen->codigo }}</option>
                                @endforeach
                            </select>
                            @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="diferencia">Diferencia</label>
                            <input class="form-control @error('diferencia') is-invalid @enderror" type="number"
                                   step="0.000001" id="diferencia" name="diferencia" required placeholder="+5 o -3">
                            @error('diferencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="motivo_linea">Motivo</label>
                            <input class="form-control" type="text" id="motivo_linea" name="motivo" maxlength="500">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">
                                Positivo si sobra fisico, negativo si falta. El costo se toma del promedio del producto.
                            </small>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Autorizacion" class="mb-3">
                <dl class="row mb-0">
                    <dt class="col-6">Autorizo</dt><dd class="col-6">{{ $ajuste->aprobadoPor?->name ?? 'Pendiente' }}</dd>
                    <dt class="col-6">Autorizado</dt><dd class="col-6">{{ $ajuste->aprobado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                    <dt class="col-6">Aplicado</dt><dd class="col-6">{{ $ajuste->aplicado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                </dl>

                @if ($ajuste->estado->esEditable())
                    <div class="alert alert-warning small mt-3 mb-0">
                        Aplicar un ajuste exige el privilegio <code>inventario.ajustes.aprobar</code>. Un ajuste
                        aplicado no se cancela: se corrige con otro en sentido contrario.
                    </div>
                @endif
            </x-card>

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
