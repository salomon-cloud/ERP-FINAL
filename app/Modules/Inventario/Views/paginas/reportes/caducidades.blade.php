@extends('layouts.app')

@section('title', 'Proximos a caducar')
@section('header', 'Lotes proximos a caducar')

@section('content')
    <x-page-header title="Proximos a caducar" subtitle="Inventario / Reportes / Caducidades">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'inventario.reportes.caducidades'])
    </x-page-header>

    <x-filter-bar placeholder="No aplica">
        <div class="col-md-2">
            <label class="form-label" for="filtro-dias">Horizonte (dias)</label>
            <input class="form-control" type="number" min="0" max="365" id="filtro-dias" name="dias" value="{{ $dias }}">
        </div>
    </x-filter-bar>

    <x-card>
        <p class="text-muted small">Lotes que caducan en los proximos {{ $dias }} dias, incluidos los que ya vencieron.</p>

        <x-table :head="['SKU', 'Producto', 'Lote', 'Caducidad', ['label' => 'Dias restantes', 'align' => 'end'], 'Estado']">
            @forelse ($filas as $lote)
                @php
                    $restantes = $lote->fecha_caducidad !== null
                        ? (int) now()->startOfDay()->diffInDays($lote->fecha_caducidad, false)
                        : null;
                @endphp
                <tr>
                    <td class="text-muted">{{ $lote->producto?->sku }}</td>
                    <td>{{ $lote->producto?->nombre }}</td>
                    <td>{{ $lote->numero_lote }}</td>
                    <td>{{ $lote->fecha_caducidad?->format('d/m/Y') ?? '--' }}</td>
                    <td class="text-end fw-bold {{ $restantes !== null && $restantes < 0 ? 'text-danger' : '' }}">
                        {{ $restantes ?? '--' }}
                    </td>
                    <td>
                        @if ($lote->esta_caducado)
                            <x-badge estado="cancelada" label="Caducado" />
                        @else
                            <x-badge estado="pendiente" label="Por vencer" />
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="Ningun lote caduca en ese horizonte." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
