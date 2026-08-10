@extends('layouts.app')

@section('title', 'Pedido')
@section('header', $pedido->numero_pedido)
@section('subtitle', 'Pedido de venta')

@section('content')
    <x-page-header :title="$pedido->numero_pedido" subtitle="Ventas / Pedidos / Detalle">
        @if ($pedido->estado->esEditable())
            <a class="btn btn-outline-primary" href="{{ route('ventas.pedidos.edit', $pedido) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @can('ventas.pedidos.confirmar')
                {{-- version_fila viaja en el formulario: es el bloqueo optimista. --}}
                <form method="POST" action="{{ route('ventas.pedidos.confirmar', $pedido) }}"
                      data-confirm="Confirmar el pedido? Se apartara la existencia para este cliente.">
                    @csrf
                    <input type="hidden" name="version_fila" value="{{ $pedido->version_fila }}">
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Confirmar</button>
                </form>
            @endcan
        @endif

        @if ($pedido->estado->admiteSurtido())
            @can('ventas.pedidos.confirmar')
                <form method="POST" action="{{ route('ventas.pedidos.surtir', $pedido) }}"
                      data-confirm="Surtir todo lo pendiente? La mercancia saldra del almacen.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-box-arrow-up me-1"></i>Surtir todo</button>
                </form>
            @endcan
        @endif

        @if ($pedido->estado->admiteFactura())
            <form method="POST" action="{{ route('ventas.pedidos.facturar', $pedido) }}"
                  data-confirm="Generar la factura de lo surtido?">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Facturar</button>
            </form>
        @endif

        @if ($pedido->estado->esCancelable())
            @can('ventas.pedidos.cancelar')
                <form method="POST" action="{{ route('ventas.pedidos.cancelar', $pedido) }}"
                      data-confirm="Cancelar el pedido? Se liberara la existencia apartada.">
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
                        <div class="fs-4 fw-bold">{{ $pedido->numero_pedido }}</div>
                        <div class="text-muted">{{ $pedido->cliente?->nombre ?? '--' }}</div>
                        <div class="small text-muted">
                            Fecha {{ $pedido->fecha?->format('d/m/Y') }}
                            @if ($pedido->fecha_entrega) · Entrega {{ $pedido->fecha_entrega->format('d/m/Y') }} @endif
                            @if ($pedido->cotizacion)
                                · Desde <a href="{{ route('ventas.cotizaciones.show', $pedido->cotizacion) }}">{{ $pedido->cotizacion->numero_cotizacion }}</a>
                            @endif
                        </div>
                    </div>
                    <x-badge :estado="$pedido->estado->color()" :label="$pedido->estado->label()" class="fs-6" />
                </div>

                <x-table :head="['Producto', 'Almacen', ['label' => 'Pedido', 'align' => 'end'], ['label' => 'Surtido', 'align' => 'end'], ['label' => 'Disponible', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], '']">
                    @forelse ($pedido->lineas as $linea)
                        @php $disponible = $disponibles[$linea->id] ?? 0.0; @endphp
                        <tr>
                            <td>
                                <div>{{ $linea->producto?->nombre ?? $linea->descripcion }}</div>
                                <small class="text-muted">{{ $linea->producto?->sku }}</small>
                            </td>
                            <td class="small">
                                @if ($linea->almacen)
                                    {{ $linea->almacen->codigo }}
                                @elseif ($linea->producto?->es_inventariable)
                                    <span class="text-danger">Falta</span>
                                @else
                                    <span class="text-muted">Servicio</span>
                                @endif
                            </td>
                            <td class="text-end">{{ (float) $linea->cantidad }}</td>
                            <td class="text-end {{ $linea->cantidad_pendiente > 0 ? 'text-warning fw-bold' : 'text-success fw-bold' }}">
                                {{ (float) $linea->cantidad_surtida }}
                            </td>
                            <td class="text-end text-muted">{{ $disponible + 0 }}</td>
                            <td class="text-end">${{ number_format((float) $linea->precio_unitario, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $linea->total, 2) }}</td>
                            <td class="text-end">
                                @if ($pedido->estado->esEditable())
                                    <form class="d-inline" method="POST"
                                          action="{{ route('ventas.pedidos.lineas.destroy', [$pedido, $linea]) }}"
                                          data-confirm="Quitar esta linea?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="8" message="Agrega al menos una linea antes de confirmar." icon="cart-check" />
                    @endforelse
                </x-table>

                <div class="d-flex justify-content-end mt-3">
                    <dl class="row mb-0" style="min-width: 280px">
                        <dt class="col-6 text-end">Subtotal</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $pedido->subtotal, 2) }}</dd>
                        <dt class="col-6 text-end">Descuentos</dt>
                        <dd class="col-6 text-end">-${{ number_format((float) $pedido->total_descuento, 2) }}</dd>
                        <dt class="col-6 text-end">Impuestos</dt>
                        <dd class="col-6 text-end">${{ number_format((float) $pedido->total_impuesto, 2) }}</dd>
                        <dt class="col-6 text-end fs-5">Total</dt>
                        <dd class="col-6 text-end fs-5 fw-bold">${{ number_format((float) $pedido->total, 2) }}</dd>
                    </dl>
                </div>

                @if ($pedido->estado->esEditable())
                    @include('ventas::partials.carrito', [
                        'accion' => route('ventas.pedidos.lineas.store', $pedido),
                        'productos' => $productos,
                        'impuestos' => $impuestos,
                        'almacenes' => $almacenes,
                    ])
                @endif

                @if ($pedido->estado->admiteSurtido())
                    <form class="mt-3 pt-3 border-top no-print" method="POST"
                          action="{{ route('ventas.pedidos.surtir', $pedido) }}">
                        @csrf
                        <div class="fw-bold mb-2">Surtido parcial</div>
                        <p class="text-muted small">
                            Deja en blanco lo que no salga todavia. Lo que no se surta sigue apartado para este cliente.
                        </p>

                        <div class="row g-2">
                            @foreach ($pedido->lineas->where('cantidad_pendiente', '>', 0) as $linea)
                                <div class="col-md-4">
                                    <label class="form-label small" for="surtir-{{ $linea->id }}">
                                        {{ $linea->producto?->nombre ?? $linea->descripcion }}
                                        <span class="text-muted">(faltan {{ $linea->cantidad_pendiente + 0 }})</span>
                                    </label>
                                    <input class="form-control form-control-sm" type="number" step="0.000001" min="0"
                                           id="surtir-{{ $linea->id }}" name="cantidades[{{ $linea->id }}]"
                                           max="{{ $linea->cantidad_pendiente }}">
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-box-arrow-up me-1"></i>Surtir lo capturado
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($pedido->estado->esEditable() && $pedido->lineas->isNotEmpty())
                <x-card title="Antes de confirmar" class="mb-3">
                    <p class="small mb-0">
                        Confirmar APARTA la existencia de cada linea: la mercancia sigue en el anaquel pero deja
                        de estar disponible para otros pedidos. Si a alguna linea le falta existencia, la
                        confirmacion se aborta entera y no se aparta nada.
                    </p>
                </x-card>
            @endif

            @if ($pedido->facturas->isNotEmpty())
                <x-card title="Facturas" class="mb-3">
                    @foreach ($pedido->facturas as $factura)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <a href="{{ route('ventas.facturas.show', $factura) }}">{{ $factura->numero_factura }}</a>
                            <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" />
                        </div>
                    @endforeach
                </x-card>
            @endif

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
