@extends('layouts.app')

@section('title', 'Stock bajo')
@section('header', 'Stock bajo')

@section('content')
    <x-page-header title="Stock bajo" subtitle="Inventario / Reportes / Stock bajo">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'inventario.reportes.stock-bajo'])
    </x-page-header>

    <x-filter-bar placeholder="No aplica">
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
            Solo aparecen los productos que tienen una regla de reorden en ese almacen. Si algo importante
            no sale aqui, probablemente le falta su regla en
            <a href="{{ route('inventario.reglas-reorden.index') }}">Minimos y maximos</a>.
        </p>

        <x-table :head="['SKU', 'Producto', 'Almacen', ['label' => 'Disponible', 'align' => 'end'], ['label' => 'Minimo', 'align' => 'end'], ['label' => 'Maximo', 'align' => 'end'], ['label' => 'Sugerido', 'align' => 'end'], ['label' => 'Dias entrega', 'align' => 'end']]">
            @forelse ($filas as $fila)
                @php
                    $reorden = (float) $fila->cantidad_reorden;
                    $maximo = (float) $fila->cantidad_maxima;
                    $objetivo = $maximo > 0 ? $maximo : (float) $fila->cantidad_minima;
                    $sugerido = $reorden > 0 ? $reorden : max($objetivo - (float) $fila->disponible, 0);
                @endphp
                <tr>
                    <td class="text-muted">{{ $fila->sku }}</td>
                    <td>{{ $fila->producto_nombre }}</td>
                    <td>{{ $fila->almacen_nombre }}</td>
                    <td class="text-end fw-bold text-danger">{{ (float) $fila->disponible }}</td>
                    <td class="text-end">{{ (float) $fila->cantidad_minima }}</td>
                    <td class="text-end">{{ $maximo ?: '--' }}</td>
                    <td class="text-end fw-bold">{{ $sugerido }}</td>
                    <td class="text-end">{{ (int) $fila->dias_entrega }}</td>
                </tr>
            @empty
                <x-empty :colspan="8" message="Todo el inventario esta por encima de su minimo." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
