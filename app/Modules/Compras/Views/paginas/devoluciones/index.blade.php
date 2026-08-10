@extends('layouts.app')

@section('title', 'Devoluciones a proveedor')
@section('header', 'Devoluciones a proveedor')
@section('subtitle', 'Lo que se regresa y por que')

@section('content')
    <x-page-header title="Devoluciones a proveedor" subtitle="Compras / Devoluciones">
        <a class="btn btn-primary" href="{{ route('compras.devoluciones.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva devolucion
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o proveedor...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-motivo">Motivo</label>
            <select class="form-select" id="filtro-motivo" name="motivo">
                <option value="">Todos</option>
                @foreach ($motivos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('motivo') === $valor)>{{ $etiqueta }}</option>
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
        <x-table :head="['Folio', 'Proveedor', 'Factura', 'Motivo', 'Fecha', ['label' => 'Lineas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
            @forelse ($devoluciones as $devolucion)
                <tr>
                    <td class="fw-bold">{{ $devolucion->numero_devolucion }}</td>
                    <td>{{ $devolucion->proveedor?->nombre ?? '--' }}</td>
                    <td class="small">{{ $devolucion->factura?->numero_factura ?? '--' }}</td>
                    <td class="small">{{ $devolucion->motivo->label() }}</td>
                    <td class="small">{{ $devolucion->fecha?->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $devolucion->lineas_count }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $devolucion->total, 2) }}</td>
                    <td><x-badge :estado="$devolucion->estado->color()" :label="$devolucion->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.devoluciones.show', $devolucion) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay devoluciones registradas." icon="arrow-return-left" />
            @endforelse
        </x-table>

        {{ $devoluciones->links() }}
    </x-card>
@endsection
