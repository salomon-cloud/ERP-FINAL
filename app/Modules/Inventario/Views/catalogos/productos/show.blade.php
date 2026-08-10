@extends('layouts.app')

@section('title', 'Producto')
@section('header', $producto->nombre)
@section('subtitle', $producto->sku)

@section('content')
    <x-page-header :title="$producto->nombre" :subtitle="'Inventario / Productos / '.$producto->sku">
        <a class="btn btn-outline-primary" href="{{ route('inventario.movimientos.index', ['producto_id' => $producto->id]) }}">
            <i class="bi bi-journal-text me-1"></i>Kardex
        </a>
        <a class="btn btn-primary" href="{{ route('inventario.productos.edit', $producto) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-card title="Datos del producto">
                <dl class="row mb-0">
                    <dt class="col-sm-5">SKU</dt><dd class="col-sm-7">{{ $producto->sku }}</dd>
                    <dt class="col-sm-5">Categoria</dt><dd class="col-sm-7">{{ $producto->categoria?->nombre_ruta ?? '--' }}</dd>
                    <dt class="col-sm-5">Unidad</dt>
                    <dd class="col-sm-7">
                        {{ $producto->unidad?->nombre ?? '--' }}
                        @if ($producto->unidad && (float) $producto->unidad->factor_base != 1.0)
                            <small class="text-muted">(1 = {{ (float) $producto->unidad->factor_base }} base)</small>
                        @endif
                    </dd>
                    <dt class="col-sm-5">Costo</dt><dd class="col-sm-7">${{ number_format((float) $producto->costo, 2) }}</dd>
                    <dt class="col-sm-5">Precio de venta</dt><dd class="col-sm-7">${{ number_format((float) $producto->precio_venta, 2) }}</dd>
                    <dt class="col-sm-5">Minimo / maximo</dt>
                    <dd class="col-sm-7">{{ (float) $producto->stock_minimo }} / {{ (float) $producto->stock_maximo }}</dd>
                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7"><x-badge :estado="$producto->estado->color()" :label="$producto->estado->label()" /></dd>
                    <dt class="col-sm-5">Se usa para</dt>
                    <dd class="col-sm-7 small">
                        {{ $producto->es_vendible ? 'Vender' : '' }}
                        {{ $producto->es_comprable ? '· Comprar' : '' }}
                        {{ $producto->es_inventariable ? '· Inventario' : '· Sin inventario' }}
                    </dd>
                    @if ($producto->descripcion)
                        <dt class="col-sm-5">Descripcion</dt><dd class="col-sm-7">{{ $producto->descripcion }}</dd>
                    @endif
                </dl>
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Existencias por almacen" subtitle="Fisica, apartada y disponible">
                <x-table :head="['Almacen', ['label' => 'Existencia', 'align' => 'end'], ['label' => 'Apartado', 'align' => 'end'], ['label' => 'Disponible', 'align' => 'end'], ['label' => 'Valor', 'align' => 'end']]">
                    @forelse ($existencias as $fila)
                        <tr>
                            <td>{{ $fila->almacen_nombre }}</td>
                            <td class="text-end">{{ (float) $fila->existencia }}</td>
                            <td class="text-end text-warning">{{ (float) $fila->apartado }}</td>
                            <td class="text-end fw-bold">{{ (float) $fila->disponible }}</td>
                            <td class="text-end">${{ number_format((float) $fila->valor_inventario, 2) }}</td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="Este producto no tiene existencia en ningun almacen." icon="clipboard-data" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-6">
            <x-card title="Codigos de barras" subtitle="El mismo articulo puede traer varios">
                <x-table :head="['Codigo', 'Principal', '']">
                    @forelse ($producto->codigosBarras as $codigo)
                        <tr>
                            <td class="font-monospace">{{ $codigo->codigo }}</td>
                            <td>{!! $codigo->es_principal ? '<i class="bi bi-star-fill text-warning"></i>' : '' !!}</td>
                            <td class="text-end">
                                <form class="d-inline" method="POST"
                                      action="{{ route('inventario.productos.codigos.destroy', [$producto, $codigo]) }}"
                                      data-confirm="Eliminar el codigo {{ $codigo->codigo }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="3" message="Sin codigos de barras." icon="upc-scan" />
                    @endforelse
                </x-table>

                <form class="row g-2 align-items-end mt-2 no-print" method="POST"
                      action="{{ route('inventario.productos.codigos.store', $producto) }}">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label" for="codigo">Nuevo codigo</label>
                        <input class="form-control @error('codigo') is-invalid @enderror" type="text"
                               id="codigo" name="codigo" maxlength="80" required>
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="es_principal" name="es_principal">
                            <label class="form-check-label" for="es_principal">Principal</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Agregar</button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card title="Lotes" subtitle="Con su fecha de caducidad">
                <x-table :head="['Lote', 'Caducidad', 'Estado', '']">
                    @forelse ($producto->lotes as $lote)
                        <tr>
                            <td>{{ $lote->numero_lote }}</td>
                            <td>{{ $lote->fecha_caducidad?->format('d/m/Y') ?? '--' }}</td>
                            <td>
                                @if ($lote->esta_caducado)
                                    <x-badge estado="cancelada" label="Caducado" />
                                @else
                                    <x-badge :estado="$lote->activo ? 'activo' : 'inactivo'" :label="$lote->activo ? 'Activo' : 'Inactivo'" />
                                @endif
                            </td>
                            <td class="text-end">
                                <form class="d-inline" method="POST"
                                      action="{{ route('inventario.productos.lotes.destroy', [$producto, $lote]) }}"
                                      data-confirm="Eliminar el lote {{ $lote->numero_lote }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="4" message="Sin lotes registrados." icon="box2" />
                    @endforelse
                </x-table>

                <form class="row g-2 align-items-end mt-2 no-print" method="POST"
                      action="{{ route('inventario.productos.lotes.store', $producto) }}">
                    @csrf
                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                    <div class="col-md-5">
                        <label class="form-label" for="numero_lote">Numero de lote</label>
                        <input class="form-control @error('numero_lote') is-invalid @enderror" type="text"
                               id="numero_lote" name="numero_lote" maxlength="60" required>
                        @error('numero_lote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="fecha_caducidad">Caducidad</label>
                        <input class="form-control" type="date" id="fecha_caducidad" name="fecha_caducidad">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Agregar</button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>

    <x-card title="Ultimos movimientos" class="mt-3">
        <x-slot:actions>
            <a class="btn btn-sm btn-outline-primary"
               href="{{ route('inventario.movimientos.index', ['producto_id' => $producto->id]) }}">Ver kardex completo</a>
        </x-slot:actions>

        <x-table :head="['Fecha', 'Tipo', 'Almacen', 'Ubicacion', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], 'Documento']">
            @forelse ($movimientos as $movimiento)
                <tr>
                    <td class="small text-muted">{{ $movimiento->aplicado_en?->format('d/m/Y H:i') }}</td>
                    <td><x-badge :estado="$movimiento->tipo_movimiento->color()" :label="$movimiento->tipo_movimiento->label()" /></td>
                    <td>{{ $movimiento->almacen?->codigo ?? '--' }}</td>
                    <td>{{ $movimiento->ubicacion?->codigo ?? '--' }}</td>
                    <td class="text-end fw-bold {{ (float) $movimiento->cantidad < 0 ? 'text-danger' : 'text-success' }}">
                        {{ (float) $movimiento->cantidad > 0 ? '+' : '' }}{{ (float) $movimiento->cantidad }}
                    </td>
                    <td class="text-end">${{ number_format((float) $movimiento->costo_unitario, 2) }}</td>
                    <td class="small text-muted">{{ $movimiento->origen_tipo ? $movimiento->origen_tipo.' #'.$movimiento->origen_id : '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="7" message="Este producto todavia no se ha movido." icon="journal-text" />
            @endforelse
        </x-table>
    </x-card>
@endsection
