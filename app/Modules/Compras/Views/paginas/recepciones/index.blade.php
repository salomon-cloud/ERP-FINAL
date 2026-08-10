@extends('layouts.app')

@section('title', 'Recepciones')
@section('header', 'Recepciones')
@section('subtitle', 'La entrada de mercancia al almacen')

@section('content')
    <x-page-header title="Recepciones" subtitle="Compras / Recepciones">
        <a class="btn btn-primary" href="{{ route('compras.recepciones.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva recepcion
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio de recepcion o de orden...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-select" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <x-table :head="['Folio', 'Orden', 'Proveedor', 'Almacen', 'Fecha', ['label' => 'Lineas', 'align' => 'end'], 'Recibio', 'Estado', '']">
            @forelse ($recepciones as $recepcion)
                <tr>
                    <td class="fw-bold">{{ $recepcion->numero_recepcion }}</td>
                    <td>
                        <a href="{{ route('compras.ordenes.show', $recepcion->orden_compra_id) }}">
                            {{ $recepcion->ordenCompra?->numero_orden }}
                        </a>
                    </td>
                    <td>{{ $recepcion->ordenCompra?->proveedor?->nombre ?? '--' }}</td>
                    <td>{{ $recepcion->almacen?->codigo ?? '--' }}</td>
                    <td class="small">{{ $recepcion->fecha?->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $recepcion->lineas_count }}</td>
                    <td class="small text-muted">{{ $recepcion->recibidoPor?->name ?? '--' }}</td>
                    <td><x-badge :estado="$recepcion->estado->color()" :label="$recepcion->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.recepciones.show', $recepcion) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay recepciones registradas." icon="box-arrow-in-down" />
            @endforelse
        </x-table>

        {{ $recepciones->links() }}
    </x-card>
@endsection
