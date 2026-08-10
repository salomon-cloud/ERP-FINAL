@extends('layouts.app')

@section('title', 'Pendientes por recibir')
@section('header', 'Pendientes por recibir')

@section('content')
    <x-page-header title="Pendientes por recibir" subtitle="Compras / Reportes / Pendientes por recibir">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'compras.reportes.pendientes-por-recibir'])
    </x-page-header>

    <x-filter-bar placeholder="No aplica">
        <div class="col-md-3">
            <label class="form-label" for="filtro-proveedor">Proveedor</label>
            <select class="form-select" id="filtro-proveedor" name="proveedor_id">
                <option value="">Todos</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((int) request('proveedor_id') === $proveedor->id)>{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ $filas->count() }} renglon(es) en camino</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Importe pendiente</div>
                <div class="fs-4 fw-bold">${{ number_format($importeTotal, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Orden', 'Entrega', 'Proveedor', 'SKU', 'Producto', ['label' => 'Pedido', 'align' => 'end'], ['label' => 'Recibido', 'align' => 'end'], ['label' => 'Pendiente', 'align' => 'end'], ['label' => 'Importe', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="fw-bold">{{ $fila->numero_orden }}</td>
                    <td class="small">{{ $fila->fecha_entrega ?? '--' }}</td>
                    <td>{{ $fila->proveedor }}</td>
                    <td class="text-muted">{{ $fila->sku }}</td>
                    <td>{{ $fila->producto }}</td>
                    <td class="text-end">{{ (float) $fila->cantidad }}</td>
                    <td class="text-end">{{ (float) $fila->cantidad_recibida }}</td>
                    <td class="text-end fw-bold text-warning">{{ (float) $fila->pendiente }}</td>
                    <td class="text-end">${{ number_format((float) $fila->importe_pendiente, 2) }}</td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay nada pendiente por recibir." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
