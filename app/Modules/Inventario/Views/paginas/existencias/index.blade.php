@extends('layouts.app')

@section('title', 'Existencias')
@section('header', 'Existencias')
@section('subtitle', 'Lo que hay, lo que esta apartado y lo que se puede prometer')

@section('content')
    <x-page-header title="Existencias" subtitle="Inventario / Existencias">
        <a class="btn btn-outline-primary" href="{{ route('inventario.reportes.existencias') }}">
            <i class="bi bi-bar-chart me-1"></i>Reporte
        </a>
    </x-page-header>

    <x-filter-bar placeholder="SKU o nombre del producto...">
        <div class="col-md-2">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->codigo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filtro-categoria">Categoria</label>
            <select class="form-select" id="filtro-categoria" name="categoria_id">
                <option value="">Todas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((int) request('categoria_id') === $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="por_ubicacion" name="por_ubicacion"
                       @checked(request()->boolean('por_ubicacion'))>
                <label class="form-check-label" for="por_ubicacion">Desglosar por ubicacion</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="incluir_en_cero" name="incluir_en_cero"
                       @checked(request()->boolean('incluir_en_cero'))>
                <label class="form-check-label" for="incluir_en_cero">Incluir los que estan en cero</label>
            </div>
        </div>
    </x-filter-bar>

    <x-card>
        <p class="text-muted small">
            <strong>Existencia</strong> es lo que hay fisicamente. <strong>Apartado</strong> es lo comprometido
            con pedidos ya confirmados. <strong>Disponible</strong> es lo unico que se puede prometer a un
            cliente nuevo. Las tres salen del libro de movimientos; no hay ninguna columna de existencia
            que alguien tenga que refrescar.
        </p>

        @php
            $columnas = ['SKU', 'Producto', 'Almacen'];
            if ($porUbicacion) { $columnas[] = 'Ubicacion'; }
            $columnas = array_merge($columnas, [
                ['label' => 'Existencia', 'align' => 'end'],
                ['label' => 'Apartado', 'align' => 'end'],
                ['label' => 'Disponible', 'align' => 'end'],
                ['label' => 'Valor', 'align' => 'end'],
                '',
            ]);
        @endphp

        <x-table :head="$columnas">
            @forelse ($existencias as $fila)
                <tr>
                    <td class="text-muted">{{ $fila->sku }}</td>
                    <td>{{ $fila->producto_nombre }}</td>
                    <td>{{ $fila->almacen_codigo }}</td>
                    @if ($porUbicacion)
                        <td>{{ $fila->ubicacion_codigo ?? 'Sin ubicacion' }}</td>
                    @endif
                    <td class="text-end">{{ (float) $fila->existencia }}</td>
                    <td class="text-end {{ (float) $fila->apartado > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                        {{ (float) $fila->apartado }}
                    </td>
                    <td class="text-end fw-bold {{ (float) $fila->disponible <= 0 ? 'text-danger' : '' }}">
                        {{ (float) $fila->disponible }}
                    </td>
                    <td class="text-end">${{ number_format((float) $fila->valor_inventario, 2) }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn"
                           href="{{ route('inventario.movimientos.index', ['producto_id' => $fila->producto_id, 'almacen_id' => $fila->almacen_id]) }}"
                           title="Ver kardex"><i class="bi bi-journal-text"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="count($columnas)" message="No hay existencias que coincidan con el filtro." icon="clipboard-data" />
            @endforelse
        </x-table>

        {{ $existencias->links() }}
    </x-card>
@endsection
