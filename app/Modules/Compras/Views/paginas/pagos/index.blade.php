@extends('layouts.app')

@section('title', 'Pagos a proveedores')
@section('header', 'Pagos a proveedores')

@section('content')
    <x-page-header title="Pagos a proveedores" subtitle="Compras / Pagos">
        <a class="btn btn-primary" href="{{ route('compras.pagos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo pago
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio, referencia o proveedor..." :dates="true">
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
        <x-table :head="['Folio', 'Proveedor', 'Factura', 'Fecha', 'Forma', 'Referencia', ['label' => 'Monto', 'align' => 'end'], 'Estado', '']">
            @forelse ($pagos as $pago)
                <tr>
                    <td class="fw-bold">{{ $pago->numero_pago }}</td>
                    <td>{{ $pago->proveedor?->nombre ?? '--' }}</td>
                    <td class="small">{{ $pago->factura?->numero_factura ?? 'A cuenta' }}</td>
                    <td class="small">{{ $pago->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $pago->forma_pago->label() }}</td>
                    <td class="small text-muted">{{ $pago->referencia ?: '--' }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $pago->monto, 2) }}</td>
                    <td><x-badge :estado="$pago->estado->color()" :label="$pago->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.pagos.show', $pago) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay pagos registrados." icon="cash-stack" />
            @endforelse
        </x-table>

        {{ $pagos->links() }}
    </x-card>
@endsection
