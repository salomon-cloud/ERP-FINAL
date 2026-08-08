@extends('layouts.app')

@section('title', 'Evaluaciones de desempeno')
@section('header', 'Evaluaciones de desempeno')

@section('content')
    <x-page-header title="Evaluaciones de desempeno" subtitle="RH / Evaluaciones">
        <a class="btn btn-primary" href="{{ route('rh.evaluaciones.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva</a>
    </x-page-header>

    <x-filter-bar search="periodo_evaluado" placeholder="Periodo evaluado (2026-S1)...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-empleado">Empleado</label>
            <select class="form-select" id="filtro-empleado" name="empleado_id">
                <option value="">Todos</option>
                @foreach ($empleados as $empleado)
                    <option value="{{ $empleado->id }}" @selected((int) request('empleado_id') === $empleado->id)>
                        {{ $empleado->nombre_completo }}
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
        <x-table :head="['Empleado', 'Periodo', 'Evaluador', ['label' => 'Calificacion', 'align' => 'end'], 'Estado', '']">
            @forelse ($evaluaciones as $evaluacion)
                <tr>
                    <td>{{ $evaluacion->empleado?->nombre_completo ?? '--' }}</td>
                    <td class="fw-bold">{{ $evaluacion->periodo_evaluado }}</td>
                    <td>{{ $evaluacion->evaluador?->nombre_completo ?? '--' }}</td>
                    <td class="text-end">
                        {{ $evaluacion->calificacion !== null ? number_format((float) $evaluacion->calificacion, 2) : '--' }}
                    </td>
                    <td><x-badge :estado="$evaluacion->estado->color()" :label="$evaluacion->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.evaluaciones.show', $evaluacion) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.evaluaciones.edit', $evaluacion) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.evaluaciones.destroy', $evaluacion) }}"
                              data-confirm="Estas seguro de eliminar esta evaluacion?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay evaluaciones registradas." icon="clipboard-check" />
            @endforelse
        </x-table>

        {{ $evaluaciones->links() }}
    </x-card>
@endsection
