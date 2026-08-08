@extends('layouts.app')

@section('title', 'Reporte de permisos')
@section('header', 'Permisos y vacaciones')

@section('content')
    <x-page-header title="Permisos y vacaciones" subtitle="RH / Reportes / Permisos">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.permisos'])
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
        <div class="col-md-4">
            <x-stat-card label="Solicitudes" :value="$permisos->count()" icon="calendar2-week" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Dias solicitados" :value="number_format($diasTotales, 2)" icon="calendar-range" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Dias sin goce" :value="number_format($diasSinGoce, 2)" icon="dash-circle"
                hint="Son los que se descuentan en nomina" />
        </div>
    </div>

    <x-card>
        <x-table :head="['Empleado', 'Tipo', 'Inicio', 'Fin', ['label' => 'Dias', 'align' => 'end'], 'Goce', 'Estado', 'Reviso']">
            @forelse ($permisos as $permiso)
                <tr>
                    <td>{{ $permiso->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $permiso->tipo->label() }}</td>
                    <td>{{ $permiso->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $permiso->fecha_fin->format('d/m/Y') }}</td>
                    <td class="text-end">{{ (float) $permiso->dias }}</td>
                    <td>{{ $permiso->con_goce ? 'Con goce' : 'Sin goce' }}</td>
                    <td><x-badge :estado="$permiso->estado->color()" :label="$permiso->estado->label()" /></td>
                    <td class="small text-muted">{{ $permiso->revisadoPor?->name ?? '--' }}</td>
                </tr>
            @empty
                <x-empty :colspan="8" message="Sin solicitudes para los filtros elegidos." icon="calendar2-week" />
            @endforelse
        </x-table>
    </x-card>
@endsection
