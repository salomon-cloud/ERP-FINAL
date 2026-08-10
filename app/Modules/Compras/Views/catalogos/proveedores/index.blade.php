@extends('layouts.app')

@section('title', 'Proveedores')
@section('header', 'Proveedores')
@section('subtitle', 'A quien le compramos')

@section('content')
    <x-page-header title="Proveedores" subtitle="Compras / Proveedores">
        <a class="btn btn-primary" href="{{ route('compras.proveedores.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo proveedor
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Nombre, codigo, RFC o correo...">
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
        <x-table :head="['Codigo', 'Proveedor', 'RFC', 'Contacto', 'Condicion', ['label' => 'Ordenes', 'align' => 'end'], ['label' => 'Saldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($proveedores as $proveedor)
                <tr>
                    <td class="text-muted">{{ $proveedor->codigo }}</td>
                    <td>
                        <div class="fw-bold">{{ $proveedor->nombre }}</div>
                        <small class="text-muted">{{ $proveedor->correo }}</small>
                    </td>
                    <td class="small">{{ $proveedor->rfc ?: '--' }}</td>
                    <td class="small">{{ $proveedor->contacto ?: '--' }}</td>
                    <td class="small">{{ $proveedor->condicionPago?->nombre ?? '--' }}</td>
                    <td class="text-end">{{ $proveedor->ordenes_count }}</td>
                    <td class="text-end fw-bold">${{ number_format($proveedor->saldo_pendiente, 2) }}</td>
                    <td><x-badge :estado="$proveedor->estado->color()" :label="$proveedor->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.proveedores.show', $proveedor) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('compras.proveedores.edit', $proveedor) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('compras.proveedores.destroy', $proveedor) }}"
                              data-confirm="Dar de baja a {{ $proveedor->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay proveedores registrados." icon="shop" />
            @endforelse
        </x-table>

        {{ $proveedores->links() }}
    </x-card>
@endsection
