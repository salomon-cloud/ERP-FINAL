@extends('layouts.app')

@section('title', 'Almacenes')
@section('header', 'Almacenes')
@section('subtitle', 'Donde vive la mercancia')

@section('content')
    <x-page-header title="Almacenes" subtitle="Inventario / Almacenes">
        <a class="btn btn-primary" href="{{ route('inventario.almacenes.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo almacen
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Codigo o nombre..." />

    <x-card>
        <x-table :head="['Codigo', 'Nombre', 'Direccion', ['label' => 'Ubicaciones', 'align' => 'end'], 'Estado', '']">
            @forelse ($almacenes as $almacen)
                <tr>
                    <td class="fw-bold">{{ $almacen->codigo }}</td>
                    <td>{{ $almacen->nombre }}</td>
                    <td class="small text-muted">{{ Str::limit($almacen->direccion, 60) ?: '--' }}</td>
                    <td class="text-end">{{ $almacen->ubicaciones_count }}</td>
                    <td><x-badge :estado="$almacen->activo ? 'activo' : 'inactivo'" :label="$almacen->activo ? 'Activo' : 'Inactivo'" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('inventario.almacenes.show', $almacen) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.almacenes.edit', $almacen) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('inventario.almacenes.destroy', $almacen) }}"
                              data-confirm="Dar de baja el almacen {{ $almacen->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay almacenes registrados." icon="building" />
            @endforelse
        </x-table>

        {{ $almacenes->links() }}
    </x-card>
@endsection
