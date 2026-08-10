@extends('layouts.app')

@section('title', 'Requisiciones')
@section('header', 'Requisiciones')
@section('subtitle', 'Lo que cada area necesita comprar')

@section('content')
    <x-page-header title="Requisiciones" subtitle="Compras / Requisiciones">
        <a class="btn btn-primary" href="{{ route('compras.requisiciones.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva requisicion
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o notas...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-departamento">Departamento</label>
            <select class="form-select" id="filtro-departamento" name="departamento_id">
                <option value="">Todos</option>
                @foreach ($departamentos as $departamento)
                    <option value="{{ $departamento->id }}" @selected((int) request('departamento_id') === $departamento->id)>
                        {{ $departamento->nombre }}
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
        <x-table :head="['Folio', 'Departamento', 'Solicita', 'Requerida', ['label' => 'Lineas', 'align' => 'end'], 'Estado', '']">
            @forelse ($requisiciones as $requisicion)
                <tr>
                    <td class="fw-bold">{{ $requisicion->numero_requisicion }}</td>
                    <td>{{ $requisicion->departamento?->nombre ?? '--' }}</td>
                    <td class="small text-muted">{{ $requisicion->solicitante?->name ?? '--' }}</td>
                    <td class="small">{{ $requisicion->fecha_requerida?->format('d/m/Y') ?? '--' }}</td>
                    <td class="text-end">{{ $requisicion->lineas_count }}</td>
                    <td><x-badge :estado="$requisicion->estado->color()" :label="$requisicion->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('compras.requisiciones.show', $requisicion) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay requisiciones registradas." icon="clipboard-plus" />
            @endforelse
        </x-table>

        {{ $requisiciones->links() }}
    </x-card>
@endsection
