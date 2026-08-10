@extends('layouts.app')

@section('title', 'Almacen')
@section('header', $almacen->nombre)
@section('subtitle', $almacen->codigo)

@section('content')
    <x-page-header :title="$almacen->nombre" :subtitle="'Inventario / Almacenes / '.$almacen->codigo">
        <a class="btn btn-outline-primary" href="{{ route('inventario.existencias.index', ['almacen_id' => $almacen->id]) }}">
            <i class="bi bi-clipboard-data me-1"></i>Existencias
        </a>
        <a class="btn btn-primary" href="{{ route('inventario.almacenes.edit', $almacen) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-card title="Datos del almacen">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codigo</dt><dd class="col-sm-8">{{ $almacen->codigo }}</dd>
                    <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">{{ $almacen->nombre }}</dd>
                    <dt class="col-sm-4">Direccion</dt><dd class="col-sm-8">{{ $almacen->direccion ?: '--' }}</dd>
                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8"><x-badge :estado="$almacen->activo ? 'activo' : 'inactivo'" :label="$almacen->activo ? 'Activo' : 'Inactivo'" /></dd>
                </dl>
            </x-card>

            <x-card title="Ubicaciones" subtitle="Pasillos, racks y anaqueles" class="mt-3">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('inventario.ubicaciones.create', ['almacen_id' => $almacen->id]) }}">
                        <i class="bi bi-plus-lg"></i> Nueva
                    </a>
                </x-slot:actions>

                <x-table :head="['Codigo', 'Nombre', 'Surtible', '']">
                    @forelse ($almacen->ubicaciones as $ubicacion)
                        <tr>
                            <td class="fw-bold">{{ $ubicacion->codigo }}</td>
                            <td>{{ $ubicacion->nombre }}</td>
                            <td>
                                @if ($ubicacion->es_surtible)
                                    <i class="bi bi-check-lg text-success"></i>
                                @else
                                    <span class="badge-soft badge-pendiente">No surtible</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.ubicaciones.edit', $ubicacion) }}"><i class="bi bi-pencil"></i></a>
                                <form class="d-inline" method="POST" action="{{ route('inventario.ubicaciones.destroy', $ubicacion) }}"
                                      data-confirm="Eliminar la ubicacion {{ $ubicacion->codigo }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="4" message="Este almacen no tiene ubicaciones." icon="geo-alt" />
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Existencias" subtitle="Los 20 productos con movimiento en este almacen">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('inventario.existencias.index', ['almacen_id' => $almacen->id]) }}">Ver todas</a>
                </x-slot:actions>

                <x-table :head="['SKU', 'Producto', ['label' => 'Existencia', 'align' => 'end'], ['label' => 'Apartado', 'align' => 'end'], ['label' => 'Disponible', 'align' => 'end']]">
                    @forelse ($existencias as $fila)
                        <tr>
                            <td class="text-muted">{{ $fila->sku }}</td>
                            <td>{{ $fila->producto_nombre }}</td>
                            <td class="text-end">{{ (float) $fila->existencia }}</td>
                            <td class="text-end text-warning">{{ (float) $fila->apartado }}</td>
                            <td class="text-end fw-bold">{{ (float) $fila->disponible }}</td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="Este almacen todavia no tiene existencia." icon="clipboard-data" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>
@endsection
