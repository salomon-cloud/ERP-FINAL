@extends('layouts.app')

@section('title', 'Ordenes de compra')
@section('header', 'Ordenes de compra')
@section('subtitle', 'El compromiso con el proveedor')

@section('content')
    <x-page-header title="Ordenes de compra" subtitle="Compras / Ordenes">
        <a class="btn btn-primary" href="{{ route('compras.ordenes.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva orden
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o proveedor..." :dates="true">
        <div class="col-md-2">
            <label class="form-label" for="filtro-proveedor">Proveedor</label>
            <select class="form-select" id="filtro-proveedor" name="proveedor_id">
                <option value="">Todos</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((int) request('proveedor_id') === $proveedor->id)>{{ $proveedor->nombre }}</option>
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
        <x-table :head="['Folio', 'Proveedor', 'Fecha', 'Entrega', ['label' => 'Lineas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
            @forelse ($ordenes as $orden)
                <tr>
                    <td class="fw-bold">{{ $orden->numero_orden }}</td>
                    <td>{{ $orden->proveedor?->nombre ?? '--' }}</td>
                    <td class="small">{{ $orden->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $orden->fecha_entrega?->format('d/m/Y') ?? '--' }}</td>
                    <td class="text-end">{{ $orden->lineas_count }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $orden->total, 2) }}</td>
                    <td><x-badge :estado="$orden->estado->color()" :label="$orden->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.ordenes.show', $orden) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay ordenes de compra." icon="file-earmark-text" />
            @endforelse
        </x-table>

        {{ $ordenes->links() }}
    </x-card>
@endsection
