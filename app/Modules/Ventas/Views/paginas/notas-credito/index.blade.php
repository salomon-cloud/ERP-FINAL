@extends('layouts.app')

@section('title', 'Notas de credito')
@section('header', 'Notas de credito')
@section('subtitle', 'Como se corrige una factura ya emitida')

@section('content')
    <x-page-header title="Notas de credito" subtitle="Ventas / Notas de credito">
        <a class="btn btn-primary" href="{{ route('ventas.notas-credito.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva nota
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o cliente...">
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
        <x-table :head="['Folio', 'Cliente', 'Factura', 'Motivo', 'Emision', ['label' => 'Lineas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
            @forelse ($notas as $nota)
                <tr>
                    <td class="fw-bold">{{ $nota->numero_nota }}</td>
                    <td>{{ $nota->cliente?->nombre ?? '--' }}</td>
                    <td class="small">{{ $nota->factura?->numero_factura ?? '--' }}</td>
                    <td class="small">{{ $nota->motivo->label() }}</td>
                    <td class="small">{{ $nota->fecha_emision?->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $nota->lineas_count }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $nota->total, 2) }}</td>
                    <td><x-badge :estado="$nota->estado->color()" :label="$nota->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.notas-credito.show', $nota) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay notas de credito." icon="arrow-return-left" />
            @endforelse
        </x-table>

        {{ $notas->links() }}
    </x-card>
@endsection
