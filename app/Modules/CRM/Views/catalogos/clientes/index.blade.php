@extends('layouts.app')

@section('title', 'Clientes')
@section('header', 'Clientes')
@section('subtitle', 'CRM')

@section('content')
    <x-page-header title="Clientes" subtitle="CRM / Clientes">
        <a class="btn btn-outline-primary" href="{{ route('ventas.clientes.index') }}">
            <i class="bi bi-people me-1"></i>Catalogo en Ventas
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
        <x-table :head="['Codigo', 'Cliente', 'RFC', 'Condicion', ['label' => 'Facturado', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], ['label' => 'Oportunidades', 'align' => 'end'], 'Estado', '']">
            @forelse ($clientes as $cliente)
                <tr>
                    <td class="text-muted">{{ $cliente->codigo }}</td>
                    <td>
                        <div class="fw-bold">{{ $cliente->nombre }}</div>
                        <small class="text-muted">{{ $cliente->correo }}</small>
                    </td>
                    <td class="small">{{ $cliente->rfc ?: '--' }}</td>
                    <td class="small">{{ $cliente->condicionPago?->nombre ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $cliente->facturado_total, 2) }}</td>
                    <td class="text-end fw-bold {{ (float) $cliente->saldo_total > (float) $cliente->limite_credito ? 'text-danger' : '' }}">
                        ${{ number_format((float) $cliente->saldo_total, 2) }}
                    </td>
                    <td class="text-end">{{ (int) $cliente->oportunidades_abiertas }}</td>
                    <td><x-badge :estado="$cliente->estado->color()" :label="$cliente->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('crm.clientes.show', $cliente) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay clientes registrados." icon="people" />
            @endforelse
        </x-table>

        {{ $clientes->links() }}
    </x-card>
@endsection
