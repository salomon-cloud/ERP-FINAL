@extends('layouts.app')

@section('title', 'Asistencias')
@section('header', 'Asistencias')
@section('subtitle', 'Control de tiempo')

@section('content')
    <x-page-header title="Asistencias" subtitle="RH / Asistencias">
        <a class="btn btn-primary" href="{{ route('rh.asistencias.create') }}"><i class="bi bi-plus-lg me-1"></i>Registrar</a>
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="Filtra por empleado abajo...">
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
        <x-table :head="['Fecha', 'Empleado', 'Entrada', 'Salida', ['label' => 'Horas', 'align' => 'end'], 'Estado', 'Notas', '']">
            @forelse ($asistencias as $asistencia)
                <tr>
                    <td>{{ $asistencia->fecha->format('d/m/Y') }}</td>
                    <td>{{ $asistencia->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $asistencia->hora_entrada ?? '--' }}</td>
                    <td>{{ $asistencia->hora_salida ?? '--' }}</td>
                    <td class="text-end">{{ number_format((float) $asistencia->horas_trabajadas, 2) }}</td>
                    <td><x-badge :estado="$asistencia->estado->color()" :label="$asistencia->estado->label()" /></td>
                    <td class="small text-muted">{{ $asistencia->notas }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.asistencias.edit', $asistencia) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.asistencias.destroy', $asistencia) }}"
                              data-confirm="Estas seguro de eliminar este registro de asistencia?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay asistencias registradas." icon="calendar-check" />
            @endforelse
        </x-table>

        {{ $asistencias->links() }}
    </x-card>
@endsection
