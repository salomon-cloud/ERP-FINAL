@extends('layouts.app')

@section('title', $periodo->codigo_periodo)
@section('header', 'Periodo '.$periodo->codigo_periodo)

@section('content')
    <x-page-header :title="$periodo->codigo_periodo" subtitle="RH / Nomina / Periodos / Detalle">
        <a class="btn btn-outline-primary" href="{{ route('rh.nomina-periodos.edit', $periodo) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <x-card title="Datos del periodo" class="mb-3">
        <dl class="row mb-0">
            <dt class="col-sm-3">Periodo</dt>
            <dd class="col-sm-9">{{ $periodo->fecha_inicio->format('d/m/Y') }} - {{ $periodo->fecha_fin->format('d/m/Y') }}</dd>
            <dt class="col-sm-3">Fecha de pago</dt><dd class="col-sm-9">{{ $periodo->fecha_pago->format('d/m/Y') }}</dd>
            <dt class="col-sm-3">Frecuencia</dt><dd class="col-sm-9">{{ $periodo->frecuencia->label() }}</dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$periodo->estado->color()" :label="$periodo->estado->label()" /></dd>
        </dl>
    </x-card>

    <x-card title="Corridas de este periodo">
        <x-table :head="['Folio', 'Estado', ['label' => 'Empleados', 'align' => 'end'], ['label' => 'Neto', 'align' => 'end'], '']">
            @forelse ($periodo->corridas as $corrida)
                <tr>
                    <td class="fw-bold">{{ $corrida->numero_corrida }}</td>
                    <td><x-badge :estado="$corrida->estado->color()" :label="$corrida->estado->label()" /></td>
                    <td class="text-end">{{ $corrida->total_empleados }}</td>
                    <td class="text-end">${{ number_format((float) $corrida->total_neto, 2) }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.nomina-corridas.show', $corrida) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="5" message="Este periodo no tiene corridas." icon="cash-stack" />
            @endforelse
        </x-table>
    </x-card>
@endsection
