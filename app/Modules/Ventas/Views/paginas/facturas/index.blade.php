@extends('layouts.app')

@section('title', 'Facturas')
@section('header', 'Facturas')
@section('subtitle', 'Las cuentas por cobrar')

@section('content')
    <x-page-header title="Facturas" subtitle="Ventas / Facturas">
        <a class="btn btn-primary" href="{{ route('ventas.facturas.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva factura
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
        <div class="col-md-auto">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="solo_vencidas" name="solo_vencidas"
                       @checked(request()->boolean('solo_vencidas'))>
                <label class="form-check-label" for="solo_vencidas">Solo vencidas</label>
            </div>
        </div>
    </x-filter-bar>

    <x-card>
        <x-table :head="['Folio', 'Cliente', 'Emision', 'Vencimiento', ['label' => 'Total', 'align' => 'end'], ['label' => 'Cobrado', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($facturas as $factura)
                <tr>
                    <td class="fw-bold">{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->cliente?->nombre ?? '--' }}</td>
                    <td class="small">{{ $factura->fecha_emision?->format('d/m/Y') }}</td>
                    <td class="small {{ $factura->esta_vencida ? 'text-danger fw-bold' : '' }}">
                        {{ $factura->fecha_vencimiento?->format('d/m/Y') ?? '--' }}
                    </td>
                    <td class="text-end">${{ number_format((float) $factura->total, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $factura->total_cobrado, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($factura->saldo, 2) }}</td>
                    <td>
                        @if ($factura->esta_vencida)
                            <x-badge estado="retardo" label="Vencida" />
                        @else
                            <x-badge :estado="$factura->estado->color()" :label="$factura->estado->label()" />
                        @endif
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.facturas.show', $factura) }}"><i class="bi bi-eye"></i></a>
                        @if ($factura->estado->admiteCobro())
                            <a class="btn btn-sm btn-outline-primary action-btn"
                               href="{{ route('ventas.cobros.create', ['factura_id' => $factura->id]) }}"
                               title="Cobrar"><i class="bi bi-cash-coin"></i></a>
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay facturas registradas." icon="receipt" />
            @endforelse
        </x-table>

        {{ $facturas->links() }}
    </x-card>
@endsection
