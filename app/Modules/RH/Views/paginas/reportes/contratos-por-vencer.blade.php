@extends('layouts.app')

@section('title', 'Contratos por vencer')
@section('header', 'Contratos por vencer')

@section('content')
    <x-page-header title="Contratos por vencer" subtitle="RH / Reportes / Contratos">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.contratos-por-vencer'])
    </x-page-header>

    <form class="row g-2 align-items-end mb-3 no-print" method="GET">
        <div class="col-md-3">
            <label class="form-label" for="dias">Vencen dentro de</label>
            <select class="form-select" id="dias" name="dias">
                @foreach ([15, 30, 60, 90] as $opcion)
                    <option value="{{ $opcion }}" @selected($dias === $opcion)>{{ $opcion }} dias</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-stat-card label="Contratos por vencer" :value="$contratos->count()" icon="file-earmark-excel"
                :hint="'En los proximos '.$dias.' dias'" />
        </div>
    </div>

    <x-card>
        <x-table :head="['Empleado', 'Numero', 'Tipo', 'Inicio', 'Vence', ['label' => 'Dias restantes', 'align' => 'end'], ['label' => 'Sueldo', 'align' => 'end'], '']">
            @forelse ($contratos as $contrato)
                @php($restantes = (int) now()->startOfDay()->diffInDays($contrato->fecha_fin, false))
                <tr>
                    <td>{{ $contrato->empleado?->nombre_completo ?? '--' }}</td>
                    <td class="text-muted">{{ $contrato->numero_contrato ?? '--' }}</td>
                    <td>{{ $contrato->tipo_contrato->label() }}</td>
                    <td>{{ $contrato->fecha_inicio->format('d/m/Y') }}</td>
                    <td class="fw-bold">{{ $contrato->fecha_fin?->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $restantes }}</td>
                    <td class="text-end">${{ number_format((float) $contrato->sueldo, 2) }}</td>
                    <td class="text-end no-print">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.contratos.edit', $contrato) }}">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="Ningun contrato vence en ese plazo." icon="check2-circle" />
            @endforelse
        </x-table>
    </x-card>
@endsection
