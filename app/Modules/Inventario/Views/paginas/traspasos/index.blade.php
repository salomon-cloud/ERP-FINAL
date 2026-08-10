@extends('layouts.app')

@section('title', 'Traspasos')
@section('header', 'Traspasos entre almacenes')
@section('subtitle', 'Salida del origen, entrada al destino')

@section('content')
    <x-page-header title="Traspasos" subtitle="Inventario / Traspasos">
        <a class="btn btn-primary" href="{{ route('inventario.traspasos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo traspaso
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio del traspaso...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-almacen">Almacen</label>
            <select class="form-select" id="filtro-almacen" name="almacen_id">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" @selected((int) request('almacen_id') === $almacen->id)>{{ $almacen->nombre }}</option>
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
        <x-table :head="['Folio', 'Origen', 'Destino', ['label' => 'Lineas', 'align' => 'end'], 'Enviado', 'Recibido', 'Estado', '']">
            @forelse ($traspasos as $traspaso)
                <tr>
                    <td class="fw-bold">{{ $traspaso->numero_traspaso }}</td>
                    <td>{{ $traspaso->almacenOrigen?->codigo ?? '--' }}</td>
                    <td>{{ $traspaso->almacenDestino?->codigo ?? '--' }}</td>
                    <td class="text-end">{{ $traspaso->lineas_count }}</td>
                    <td class="small text-muted">{{ $traspaso->enviado_en?->format('d/m/Y') ?? '--' }}</td>
                    <td class="small text-muted">{{ $traspaso->recibido_en?->format('d/m/Y') ?? '--' }}</td>
                    <td><x-badge :estado="$traspaso->estado->color()" :label="$traspaso->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('inventario.traspasos.show', $traspaso) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay traspasos registrados." icon="arrow-left-right" />
            @endforelse
        </x-table>

        {{ $traspasos->links() }}
    </x-card>
@endsection
