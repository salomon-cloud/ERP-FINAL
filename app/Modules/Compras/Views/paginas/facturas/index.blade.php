@extends('layouts.app')

@section('title', 'Facturas de proveedor')
@section('header', 'Facturas de proveedor')
@section('subtitle', 'Las cuentas por pagar')

@section('content')
    <x-page-header title="Facturas de proveedor" subtitle="Compras / Facturas">
        <a class="btn btn-primary" href="{{ route('compras.facturas.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva factura
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
        <x-table :head="['Folio', 'Proveedor', 'Orden', 'Fecha', 'Vencimiento', ['label' => 'Total', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($facturas as $factura)
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->proveedor?->nombre ?? '--' }}</td>
                    <td class="small">{{ $factura->ordenCompra?->numero_orden ?? '--' }}</td>
                    <td class="small">{{ $factura->fecha?->format('d/m/Y') }}</td>
                    <td class="small {{ $factura->esta_vencida ? 'text-danger fw-bold' : '' }}">
                        {{ $factura->fecha_vencimiento?->format('d/m/Y') ?? '--' }}
                    </td>
                    <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($factura->saldo, 2) }}</td>
                    <td><x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.facturas.show', $factura) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay facturas de proveedor." icon="receipt" />
            @endforelse
        </x-table>

        {{ $facturas->links() }}
    </x-card>
@endsection
