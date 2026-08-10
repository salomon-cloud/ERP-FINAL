@extends('layouts.app')

@section('title', 'Traspaso')
@section('header', $traspaso->numero_traspaso)
@section('subtitle', 'Traspaso entre almacenes')

@section('content')
    <x-page-header :title="$traspaso->numero_traspaso" subtitle="Inventario / Traspasos / Detalle">
        @if ($traspaso->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('inventario.traspasos.edit', $traspaso) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST" action="{{ route('inventario.traspasos.enviar', $traspaso) }}"
                  data-confirm="Enviar el traspaso? La mercancia saldra del almacen de origen.">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-truck me-1"></i>Enviar</button>
            </form>
            <form method="POST" action="{{ route('inventario.traspasos.cancelar', $traspaso) }}"
                  data-confirm="Cancelar el traspaso {{ $traspaso->numero_traspaso }}?">
                @csrf
                <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
            </form>
        @elseif ($traspaso->estado === \App\Modules\Inventario\Enums\EstadoTraspaso::EnTransito)
            <form method="POST" action="{{ route('inventario.traspasos.recibir', $traspaso) }}"
                  data-confirm="Confirmar la recepcion en el almacen de destino?">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-box-arrow-in-down me-1"></i>Recibir</button>
            </form>
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $traspaso->numero_traspaso }}</div>
                        <div class="text-muted">
                            {{ $traspaso->almacenOrigen?->nombre ?? '--' }}
                            <i class="bi bi-arrow-right mx-2"></i>
                            {{ $traspaso->almacenDestino?->nombre ?? '--' }}
                        </div>
                    </div>
                    <x-badge :estado="$traspaso->estado->color()" :label="$traspaso->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', 'Lote', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Disponible en origen', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], '']">
                    @forelse ($traspaso->lineas as $linea)
                        @php $disponible = $disponibles[$linea->producto_id] ?? 0.0; @endphp
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="small">{{ $linea->lote?->numero_lote ?? '--' }}</td>
                            <td class="text-end fw-bold">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end {{ $disponible < (float) $linea->cantidad ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $disponible + 0 }}
                            </td>
                            <td class="text-end">${{ number_format((float) $linea->costo_unitario, 2) }}</td>
                            <td class="text-end">
                                @if ($traspaso->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('inventario.traspasos.lineas.destroy', [$traspaso, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="6" message="Agrega al menos una linea antes de enviar." icon="box-seam" />
                    @endforelse
                </x-table>

                @if ($traspaso->estado->esEditable())
                    <form class="row g-2 align-items-end mt-3 no-print" method="POST"
                          action="{{ route('inventario.traspasos.lineas.store', $traspaso) }}">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label" for="producto_id">Producto</label>
                            <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
                                <option value="">Selecciona...</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                                @endforeach
                            </select>
                            @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cantidad">Cantidad</label>
                            <input class="form-control @error('cantidad') is-invalid @enderror" type="number"
                                   step="0.000001" min="0.000001" id="cantidad" name="cantidad" required>
                            @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="costo_unitario">Costo</label>
                            <input class="form-control" type="number" step="0.000001" min="0"
                                   id="costo_unitario" name="costo_unitario" placeholder="Promedio">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Seguimiento" class="mb-3">
                <dl class="row mb-0">
                    <dt class="col-6">Solicito</dt><dd class="col-6">{{ $traspaso->solicitadoPor?->name ?? '--' }}</dd>
                    <dt class="col-6">Autorizo</dt><dd class="col-6">{{ $traspaso->aprobadoPor?->name ?? '--' }}</dd>
                    <dt class="col-6">Enviado</dt><dd class="col-6">{{ $traspaso->enviado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                    <dt class="col-6">Recibido</dt><dd class="col-6">{{ $traspaso->recibido_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                </dl>

                @if ($traspaso->estado === \App\Modules\Inventario\Enums\EstadoTraspaso::EnTransito)
                    <div class="alert alert-info small mt-3 mb-0">
                        La mercancia ya salio del origen y todavia no llega al destino. Un traspaso en
                        transito no se cancela: se recibe.
                    </div>
                @endif
            </x-card>

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
