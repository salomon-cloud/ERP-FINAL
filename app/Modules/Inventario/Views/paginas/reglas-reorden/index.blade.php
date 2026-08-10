@extends('layouts.app')

@section('title', 'Minimos y maximos')
@section('header', 'Minimos y maximos por almacen')
@section('subtitle', 'Lo que dispara la alerta de compra')

@section('content')
    <x-page-header title="Minimos y maximos" subtitle="Inventario / Minimos y maximos">
        <a class="btn btn-outline-primary" href="{{ route('inventario.reportes.stock-bajo') }}">
            <i class="bi bi-exclamation-triangle me-1"></i>Ver lo que falta
        </a>
        <a class="btn btn-primary" href="{{ route('inventario.reglas-reorden.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva regla
        </a>
    </x-page-header>

    <x-filter-bar placeholder="SKU o nombre del producto...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->nombre }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <p class="text-muted small">
            Sin una regla no hay alerta. El comando <code>inventario:reorden</code> corre a diario y
            compara el disponible de cada regla contra su minimo.
        </p>

        <x-table :head="['Producto', 'Almacen', ['label' => 'Minimo', 'align' => 'end'], ['label' => 'Maximo', 'align' => 'end'], ['label' => 'A reordenar', 'align' => 'end'], ['label' => 'Dias entrega', 'align' => 'end'], 'Estado', '']">
            @forelse ($reglas as $regla)
                <tr>
                    <td>
                        <div>{{ $regla->producto?->nombre ?? '--' }}</div>
                        <small class="text-muted">{{ $regla->producto?->sku }}</small>
                    </td>
                    <td>{{ $regla->almacen?->codigo ?? '--' }}</td>
                    <td class="text-end fw-bold">{{ (float) $regla->cantidad_minima }}</td>
                    <td class="text-end">{{ (float) $regla->cantidad_maxima }}</td>
                    <td class="text-end">{{ (float) $regla->cantidad_reorden ?: '--' }}</td>
                    <td class="text-end">{{ $regla->dias_entrega }}</td>
                    <td><x-badge :estado="$regla->activo ? 'activo' : 'inactivo'" :label="$regla->activo ? 'Activa' : 'Inactiva'" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.reglas-reorden.edit', $regla) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('inventario.reglas-reorden.destroy', $regla) }}"
                              data-confirm="Eliminar esta regla de reorden?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay reglas de reorden configuradas." icon="bell" />
            @endforelse
        </x-table>

        {{ $reglas->links() }}
    </x-card>
@endsection
