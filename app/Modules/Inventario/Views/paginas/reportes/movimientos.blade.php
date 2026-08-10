@extends('layouts.app')

@section('title', 'Kardex por periodo')
@section('header', 'Kardex por periodo')

@section('content')
    <x-page-header title="Kardex por periodo" subtitle="Inventario / Reportes / Movimientos">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'inventario.reportes.movimientos'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica">
        <div class="col-md-2">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->codigo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="filtro-tipo">Tipo</label>
            <select class="form-select" id="filtro-tipo" name="tipo_movimiento">
                <option value="">Todos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('tipo_movimiento') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <p class="text-muted small">{{ $filas->count() }} movimiento(s). El reporte se limita a 2000 renglones; afina el rango de fechas si necesitas mas.</p>

        <x-table :head="['Fecha', 'Tipo', 'SKU', 'Producto', 'Almacen', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], 'Documento', 'Usuario']">
            @forelse ($filas as $movimiento)
                <tr>
                    <td class="small text-nowrap">{{ $movimiento->aplicado_en?->format('d/m/Y H:i') }}</td>
                    <td><x-badge :estado="$movimiento->tipo_movimiento->color()" :label="$movimiento->tipo_movimiento->label()" /></td>
                    <td class="text-muted">{{ $movimiento->producto?->sku }}</td>
                    <td>{{ $movimiento->producto?->nombre }}</td>
                    <td>{{ $movimiento->almacen?->codigo }}</td>
                    <td class="text-end fw-bold {{ (float) $movimiento->cantidad < 0 ? 'text-danger' : 'text-success' }}">
                        {{ (float) $movimiento->cantidad > 0 ? '+' : '' }}{{ (float) $movimiento->cantidad }}
                    </td>
                    <td class="text-end">${{ number_format((float) $movimiento->costo_unitario, 2) }}</td>
                    <td class="small text-muted">{{ $movimiento->origen_tipo ? $movimiento->origen_tipo.' #'.$movimiento->origen_id : '--' }}</td>
                    <td class="small text-muted">{{ $movimiento->aplicadoPor?->name ?? '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay movimientos en ese periodo." icon="journal-text" />
            @endforelse
        </x-table>
    </x-card>
@endsection
