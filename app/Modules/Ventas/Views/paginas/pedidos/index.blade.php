@extends('layouts.app')

@section('title', 'Pedidos')
@section('header', 'Pedidos')
@section('subtitle', 'La venta confirmada, con existencia apartada')

@section('content')
    <x-page-header title="Pedidos" subtitle="Ventas / Pedidos">
        <a class="btn btn-primary" href="{{ route('ventas.pedidos.create') }}">
            <i class="bi bi-cart-plus me-1"></i>Nuevo pedido
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o cliente..." :dates="true">
        <div class="col-md-2">
            <label class="form-label" for="filtro-cliente">Cliente</label>
            <select class="form-select" id="filtro-cliente" name="cliente_id">
                <option value="">Todos</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((int) request('cliente_id') === $cliente->id)>{{ $cliente->nombre }}</option>
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
        <x-table :head="['Folio', 'Cliente', 'Fecha', 'Entrega', ['label' => 'Lineas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
            @forelse ($pedidos as $pedido)
                <tr>
                    <td class="fw-bold">{{ $pedido->numero_pedido }}</td>
                    <td>{{ $pedido->cliente?->nombre ?? '--' }}</td>
                    <td class="small">{{ $pedido->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $pedido->fecha_entrega?->format('d/m/Y') ?? '--' }}</td>
                    <td class="text-end">{{ $pedido->lineas_count }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $pedido->total, 2) }}</td>
                    <td><x-badge :estado="$pedido->estado->color()" :label="$pedido->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.pedidos.show', $pedido) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay pedidos registrados." icon="cart-check" />
            @endforelse
        </x-table>

        {{ $pedidos->links() }}
    </x-card>
@endsection
