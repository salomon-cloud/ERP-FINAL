@extends('layouts.app')

@section('title', 'Conteos fisicos')
@section('header', 'Conteos fisicos')
@section('subtitle', 'Lo que dice el sistema contra lo que hay en el anaquel')

@section('content')
    <x-page-header title="Conteos fisicos" subtitle="Inventario / Conteos">
        <a class="btn btn-primary" href="{{ route('inventario.conteos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo conteo
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio del conteo...">
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
        <x-table :head="['Folio', 'Almacen', 'Ubicacion', ['label' => 'Lineas', 'align' => 'end'], 'Conto', 'Cerrado', 'Estado', '']">
            @forelse ($conteos as $conteo)
                <tr>
                    <td class="fw-bold">{{ $conteo->numero_conteo }}</td>
                    <td>{{ $conteo->almacen?->codigo ?? '--' }}</td>
                    <td>{{ $conteo->ubicacion?->codigo ?? 'Todo el almacen' }}</td>
                    <td class="text-end">{{ $conteo->lineas_count }}</td>
                    <td class="small text-muted">{{ $conteo->contadoPor?->name ?? '--' }}</td>
                    <td class="small text-muted">{{ $conteo->cerrado_en?->format('d/m/Y') ?? '--' }}</td>
                    <td><x-badge :estado="$conteo->estado->color()" :label="$conteo->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('inventario.conteos.show', $conteo) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay conteos registrados." icon="ui-checks" />
            @endforelse
        </x-table>

        {{ $conteos->links() }}
    </x-card>
@endsection
