@extends('layouts.app')

@section('title', 'Reporte de asistencia')
@section('header', 'Asistencia')

@section('content')
    <x-page-header title="Reporte de asistencia" subtitle="RH / Reportes / Asistencia">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.asistencias'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="Filtra con los selectores...">
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

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-stat-card label="Registros" :value="$asistencias->count()" icon="calendar-check" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Horas trabajadas" :value="number_format($horas, 2)" icon="clock" />
        </div>
        @foreach ($porEstado as $etiqueta => $total)
            <div class="col-md-2">
                <x-stat-card :label="$etiqueta" :value="$total" icon="dot" />
            </div>
        @endforeach
    </div>

    <x-card>
        <x-table :head="['Fecha', 'Empleado', 'Entrada', 'Salida', ['label' => 'Horas', 'align' => 'end'], 'Estado', 'Notas']">
            @forelse ($asistencias as $asistencia)
                <tr>
                    <td>{{ $asistencia->fecha->format('d/m/Y') }}</td>
                    <td>{{ $asistencia->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $asistencia->hora_entrada ?? '--' }}</td>
                    <td>{{ $asistencia->hora_salida ?? '--' }}</td>
                    <td class="text-end">{{ number_format((float) $asistencia->horas_trabajadas, 2) }}</td>
                    <td><x-badge :estado="$asistencia->estado->color()" :label="$asistencia->estado->label()" /></td>
                    <td class="small text-muted">{{ $asistencia->notas }}</td>
                </tr>
            @empty
                <x-empty :colspan="7" message="Sin registros para los filtros elegidos." icon="calendar-check" />
            @endforelse
        </x-table>
    </x-card>
@endsection
