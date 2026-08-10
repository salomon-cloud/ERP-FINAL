@extends('layouts.app')

@section('title', 'Cobros')
@section('header', 'Cobros')

@section('content')
    <x-page-header title="Cobros" subtitle="Ventas / Cobros">
        <a class="btn btn-primary" href="{{ route('ventas.cobros.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo cobro
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio, referencia o cliente..." :dates="true">
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
        <x-table :head="['Folio', 'Cliente', 'Factura', 'Fecha', 'Forma', 'Referencia', ['label' => 'Monto', 'align' => 'end'], 'Estado', '']">
            @forelse ($cobros as $cobro)
                <tr>
                    <td class="fw-bold">{{ $cobro->numero_cobro }}</td>
                    <td>{{ $cobro->cliente?->nombre ?? '--' }}</td>
                    <td class="small">{{ $cobro->factura?->numero_factura ?? 'A cuenta' }}</td>
                    <td class="small">{{ $cobro->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $cobro->forma_pago->label() }}</td>
                    <td class="small text-muted">{{ $cobro->referencia ?: '--' }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $cobro->monto, 2) }}</td>
                    <td><x-badge :estado="$cobro->estado->color()" :label="$cobro->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.cobros.show', $cobro) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay cobros registrados." icon="cash-coin" />
            @endforelse
        </x-table>

        {{ $cobros->links() }}
    </x-card>
@endsection
