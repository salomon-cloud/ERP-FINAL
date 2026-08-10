@extends('layouts.app')

@section('title', 'Ajustes de inventario')
@section('header', 'Ajustes de inventario')
@section('subtitle', 'Merma, rotura, sobrante y correcciones, siempre con motivo')

@section('content')
    <x-page-header title="Ajustes de inventario" subtitle="Inventario / Ajustes">
        <a class="btn btn-primary" href="{{ route('inventario.ajustes.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo ajuste
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o motivo...">
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
        <x-table :head="['Folio', 'Motivo', ['label' => 'Lineas', 'align' => 'end'], 'Autorizo', 'Aplicado', 'Estado', '']">
            @forelse ($ajustes as $ajuste)
                <tr>
                    <td class="fw-bold">{{ $ajuste->numero_ajuste }}</td>
                    <td>{{ Str::limit($ajuste->motivo, 70) }}</td>
                    <td class="text-end">{{ $ajuste->lineas_count }}</td>
                    <td class="small text-muted">{{ $ajuste->aprobadoPor?->name ?? '--' }}</td>
                    <td class="small text-muted">{{ $ajuste->aplicado_en?->format('d/m/Y') ?? '--' }}</td>
                    <td><x-badge :estado="$ajuste->estado->color()" :label="$ajuste->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('inventario.ajustes.show', $ajuste) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay ajustes registrados." icon="sliders" />
            @endforelse
        </x-table>

        {{ $ajustes->links() }}
    </x-card>
@endsection
