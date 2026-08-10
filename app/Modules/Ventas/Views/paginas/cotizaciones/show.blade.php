@extends('layouts.app')

@section('title', 'Cotizacion')
@section('header', $cotizacion->numero_cotizacion)
@section('subtitle', 'Cotizacion')

@section('content')
    <x-page-header :title="$cotizacion->numero_cotizacion" subtitle="Ventas / Cotizaciones / Detalle">
        @if ($cotizacion->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('ventas.cotizaciones.edit', $cotizacion) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST" action="{{ route('ventas.cotizaciones.enviar', $cotizacion) }}"
                  data-confirm="Marcar la cotizacion como enviada al cliente?">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Enviar</button>
            </form>
        @endif

        @if ($cotizacion->estado->esCancelable())
            <form method="POST" action="{{ route('ventas.cotizaciones.cancelar', $cotizacion) }}"
                  data-confirm="Cancelar la cotizacion {{ $cotizacion->numero_cotizacion }}?">
                @csrf
                <button class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
            </form>
        @endif
    </x-page-header>

    @if ($cotizacion->esta_vencida)
        <div class="alert alert-warning small">
            <i class="bi bi-hourglass-bottom me-1"></i>
            Esta cotizacion vencio el {{ $cotizacion->vigencia->format('d/m/Y') }}. Ya no se puede aceptar:
            genera una nueva con precios vigentes.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $cotizacion->numero_cotizacion }}</div>
                        <div class="text-muted">{{ $cotizacion->cliente?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            Fecha {{ $cotizacion->fecha?->format('d/m/Y') }}
                            @if ($cotizacion->vigencia) · Vigente hasta {{ $cotizacion->vigencia->format('d/m/Y') }} @endif
                            @if ($cotizacion->listaPrecio) · Lista {{ $cotizacion->listaPrecio->nombre }} @endif
                        </div>
                    </div>
                    <x-badge :estado="$cotizacion->estado->color()" :label="$cotizacion->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Desc.', 'align' => 'end'], ['label' => 'Impuesto', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($cotizacion->lineas as $linea)
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
                            <td class="text-end">
                                @if ($cotizacion->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('ventas.cotizaciones.lineas.destroy', [$cotizacion, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="7" message="Agrega al menos una linea antes de enviar." icon="file-earmark-text" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 280px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $cotizacion->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Descuentos</dt>
                        <dd class="col-6 text-end">-${{ number_format((float) $cotizacion->total_descuento, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $cotizacion->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $cotizacion->total, 2) }}</dd>
                    </dl>
                </div>

                @if ($cotizacion->estado->esEditable())
                    @include('ventas::partials.carrito', [
                        'accion' => route('ventas.cotizaciones.lineas.store', $cotizacion),
                        'productos' => $productos,
                        'impuestos' => $impuestos,
                        'almacenes' => null,
                    ])
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($cotizacion->estado->admiteRespuesta())
                <x-card title="Respuesta del cliente" class="mb-3">
                    <form method="POST" action="{{ route('ventas.cotizaciones.responder', $cotizacion) }}">
                        @csrf @method('PATCH')
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit" name="decision" value="aceptar"
                                    @disabled($cotizacion->esta_vencida)>
                                <i class="bi bi-check-lg me-1"></i>Aceptar
                            </button>
                            <button class="btn btn-outline-danger" type="submit" name="decision" value="rechazar">
                                <i class="bi bi-x-lg me-1"></i>Rechazar
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif

            @if ($cotizacion->estado->esConvertible())
                <x-card title="Convertir en pedido" subtitle="De que almacen va a salir" class="mb-3">
                    <form method="POST" action="{{ route('ventas.cotizaciones.convertir', $cotizacion) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="almacen_id">Almacen</label>
                            <select class="form-select" id="almacen_id" name="almacen_id">
                                <option value="">Elegirlo despues, linea por linea</option>
                                @foreach ($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}">{{ $almacen->codigo }} - {{ $almacen->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-arrow-right-circle me-1"></i>Generar pedido
                        </button>
                        <small class="text-muted d-block mt-2">
                            El pedido nace en borrador. Confirmarlo es lo que aparta la existencia.
                        </small>
                    </form>
                </x-card>
            @endif

            @if ($cotizacion->pedidos->isNotEmpty())
                <x-card title="Pedidos generados" class="mb-3">
                    @foreach ($cotizacion->pedidos as $pedido)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('ventas.pedidos.show', $pedido) }}">{{ $pedido->numero_pedido }}</a>
                            <x-badge :estado="$pedido->estado->color()" :label="$pedido->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
