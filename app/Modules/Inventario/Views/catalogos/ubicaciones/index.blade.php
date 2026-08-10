@extends('layouts.app')

@section('title', 'Ubicaciones')
@section('header', 'Ubicaciones')
@section('subtitle', 'Pasillos, racks y anaqueles de cada almacen')

@section('content')
    <x-page-header title="Ubicaciones" subtitle="Inventario / Ubicaciones">
        <a class="btn btn-primary" href="{{ route('inventario.ubicaciones.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva ubicacion
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Codigo o nombre...">
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
        <x-table :head="['Almacen', 'Codigo', 'Nombre', 'Surtible', 'Estado', '']">
            @forelse ($ubicaciones as $ubicacion)
                <tr>
                    <td>{{ $ubicacion->almacen?->codigo ?? '--' }}</td>
                    <td class="fw-bold">{{ $ubicacion->codigo }}</td>
                    <td>{{ $ubicacion->nombre }}</td>
                    <td>
                        @if ($ubicacion->es_surtible)
                            <i class="bi bi-check-lg text-success"></i>
                        @else
                            <span class="badge-soft badge-pendiente">No surtible</span>
                        @endif
                    </td>
                    <td><x-badge :estado="$ubicacion->activo ? 'activo' : 'inactivo'" :label="$ubicacion->activo ? 'Activa' : 'Inactiva'" /></td>
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
                <x-empty :colspan="6" message="No hay ubicaciones registradas." icon="geo-alt" />
            @endforelse
        </x-table>

        {{ $ubicaciones->links() }}
    </x-card>
@endsection
