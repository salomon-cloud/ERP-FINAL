@extends('layouts.app')

@section('title', 'Kardex')
@section('header', 'Kardex de inventario')
@section('subtitle', 'El libro de movimientos: solo lectura')

@section('content')
    <x-page-header title="Kardex" subtitle="Inventario / Movimientos">
        <a class="btn btn-outline-primary" href="{{ route('inventario.reportes.movimientos') }}">
            <i class="bi bi-bar-chart me-1"></i>Reporte
        </a>
    </x-page-header>

    <x-filter-bar placeholder="SKU o nombre del producto..." :dates="true">
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
        <p class="text-muted small">
            Un movimiento no se edita ni se borra nunca: si algo salio mal, se genera el movimiento
            contrario y los dos quedan aqui. Esa es la razon de que la existencia siempre cuadre con
            su historia.
        </p>

        <x-table :head="['Fecha', 'Tipo', 'Producto', 'Almacen', 'Ubicacion', 'Lote', ['label' => 'Cantidad', 'align' => 'end'], ['label' => 'Costo', 'align' => 'end'], 'Documento', 'Usuario', 'Estado']">
            @forelse ($movimientos as $movimiento)
                <tr>
                    <td class="small text-muted text-nowrap">{{ $movimiento->aplicado_en?->format('d/m/Y H:i') }}</td>
                    <td><x-badge :estado="$movimiento->tipo_movimiento->color()" :label="$movimiento->tipo_movimiento->label()" /></td>
                    <td>
                        <div>{{ $movimiento->producto?->nombre ?? '--' }}</div>
                        <small class="text-muted">{{ $movimiento->producto?->sku }}</small>
                    </td>
                    <td>{{ $movimiento->almacen?->codigo ?? '--' }}</td>
                    <td>{{ $movimiento->ubicacion?->codigo ?? '--' }}</td>
                    <td class="small">{{ $movimiento->lote?->numero_lote ?? '--' }}</td>
                    <td class="text-end fw-bold {{ (float) $movimiento->cantidad < 0 ? 'text-danger' : 'text-success' }}">
                        {{ (float) $movimiento->cantidad > 0 ? '+' : '' }}{{ (float) $movimiento->cantidad }}
                    </td>
                    <td class="text-end">${{ number_format((float) $movimiento->costo_unitario, 2) }}</td>
                    <td class="small text-muted">{{ $movimiento->origen_tipo ? $movimiento->origen_tipo.' #'.$movimiento->origen_id : '--' }}</td>
                    <td class="small text-muted">{{ $movimiento->aplicadoPor?->name ?? '--' }}</td>
                    <td><x-badge :estado="$movimiento->estado->color()" :label="$movimiento->estado->label()" /></td>
                </tr>
            @empty
                <x-empty :colspan="11" message="No hay movimientos que coincidan con el filtro." icon="journal-text" />
            @endforelse
        </x-table>

        {{ $movimientos->links() }}
    </x-card>
@endsection
