@extends('layouts.app')

@section('title', 'Clientes')
@section('header', 'Clientes')
@section('subtitle', 'A quien le vendemos')

@section('content')
    <x-page-header title="Clientes" subtitle="Ventas / Clientes">
        <a class="btn btn-primary" href="{{ route('ventas.clientes.create') }}">
            <i class="bi bi-person-plus me-1"></i>Nuevo cliente
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Nombre, codigo, RFC o correo...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-condicion">Condicion de pago</label>
            <select class="form-select" id="filtro-condicion" name="condicion_pago_id">
                <option value="">Todas</option>
                @foreach ($condicionesPago as $condicion)
                    <option value="{{ $condicion->id }}" @selected((int) request('condicion_pago_id') === $condicion->id)>
                        {{ $condicion->nombre }}
                    </option>
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
        <x-table :head="['Codigo', 'Cliente', 'RFC', 'Condicion', 'Lista', ['label' => 'Limite', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($clientes as $cliente)
                <tr>
                    <td class="text-muted">{{ $cliente->codigo }}</td>
                    <td>
                        <div class="fw-bold">{{ $cliente->nombre }}</div>
                        <small class="text-muted">{{ $cliente->correo }}</small>
                    </td>
                    <td class="small">{{ $cliente->rfc ?: '--' }}</td>
                    <td class="small">{{ $cliente->condicionPago?->nombre ?? '--' }}</td>
                    <td class="small">{{ $cliente->listaPrecio?->nombre ?? 'Predeterminada' }}</td>
                    <td class="text-end">${{ number_format((float) $cliente->limite_credito, 2) }}</td>
                    <td class="text-end fw-bold {{ $cliente->credito_disponible < 0 ? 'text-danger' : '' }}">
                        ${{ number_format($cliente->saldo_pendiente, 2) }}
                    </td>
                    <td><x-badge :estado="$cliente->estado->color()" :label="$cliente->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.clientes.show', $cliente) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('ventas.clientes.edit', $cliente) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('ventas.clientes.destroy', $cliente) }}"
                              data-confirm="Dar de baja a {{ $cliente->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay clientes registrados." icon="people" />
            @endforelse
        </x-table>

        {{ $clientes->links() }}
    </x-card>
@endsection
