@extends('layouts.app')

@section('title', 'Corridas de nomina')
@section('header', 'Corridas de nomina')
@section('subtitle', 'Procesamiento de la nomina')

@section('content')
    <x-page-header title="Corridas de nomina" subtitle="RH / Nomina / Corridas">
        <a class="btn btn-primary" href="{{ route('rh.nomina-corridas.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva corrida</a>
    </x-page-header>

    <x-filter-bar placeholder="Folio de la corrida...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-periodo">Periodo</label>
            <select class="form-select" id="filtro-periodo" name="periodo_id">
                <option value="">Todos</option>
                @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}" @selected((int) request('periodo_id') === $periodo->id)>
                        {{ $periodo->codigo_periodo }}
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
        <x-table :head="['Folio', 'Periodo', 'Estado', ['label' => 'Empleados', 'align' => 'end'], ['label' => 'Percepciones', 'align' => 'end'], ['label' => 'Deducciones', 'align' => 'end'], ['label' => 'Neto', 'align' => 'end'], '']">
            @forelse ($corridas as $corrida)
                <tr>
                    <td class="fw-bold">{{ $corrida->numero_corrida }}</td>
                    <td>{{ $corrida->periodo?->codigo_periodo ?? '--' }}</td>
                    <td><x-badge :estado="$corrida->estado->color()" :label="$corrida->estado->label()" /></td>
                    <td class="text-end">{{ $corrida->total_empleados }}</td>
                    <td class="text-end">${{ number_format((float) $corrida->total_percepciones, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $corrida->total_deducciones, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $corrida->total_neto, 2) }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.nomina-corridas.show', $corrida) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay corridas de nomina." icon="cash-stack" />
            @endforelse
        </x-table>

        {{ $corridas->links() }}
    </x-card>
@endsection
