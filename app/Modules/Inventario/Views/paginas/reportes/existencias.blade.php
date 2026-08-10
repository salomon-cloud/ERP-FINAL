@extends('layouts.app')

@section('title', 'Existencias valorizadas')
@section('header', 'Existencias valorizadas')

@section('content')
    <x-page-header title="Existencias valorizadas" subtitle="Inventario / Reportes / Existencias">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'inventario.reportes.existencias'])
    </x-page-header>

    <x-filter-bar placeholder="SKU o nombre...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="filtro-categoria">Categoria</label>
            <select class="form-select" id="filtro-categoria" name="categoria_id">
                <option value="">Todas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((int) request('categoria_id') === $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} renglon(es)</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Valor total</div>
                <div class="fs-4 fw-bold">${{ number_format($valorTotal, 2) }}</div>
            </div>
        </div>

        <x-table :head="['SKU', 'Producto', 'Almacen', ['label' => 'Existencia', 'align' => 'end'], ['label' => 'Apartado', 'align' => 'end'], ['label' => 'Disponible', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], ['label' => 'Valor', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="text-muted">{{ $fila->sku }}</td>
                    <td>{{ $fila->producto_nombre }}</td>
                    <td>{{ $fila->almacen_nombre }}</td>
                    <td class="text-end">{{ (float) $fila->existencia }}</td>
                    <td class="text-end">{{ (float) $fila->apartado }}</td>
                    <td class="text-end fw-bold">{{ (float) $fila->disponible }}</td>
                    <td class="text-end">${{ number_format((float) $fila->costo, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $fila->valor_inventario, 2) }}</td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay existencias que coincidan con el filtro." icon="clipboard-data" />
            @endforelse
        </x-table>
    </x-card>
@endsection
