@extends('layouts.app')

@section('title', 'Reporte de plantilla')
@section('header', 'Plantilla de empleados')

@section('content')
    <x-page-header title="Plantilla de empleados" subtitle="RH / Reportes / Plantilla">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.empleados'])
    </x-page-header>

    <x-filter-bar search="buscar" placeholder="Filtra con los selectores...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-departamento">Departamento</label>
            <select class="form-select" id="filtro-departamento" name="departamento_id">
                <option value="">Todos</option>
                @foreach ($departamentos as $departamento)
                    <option value="{{ $departamento->id }}" @selected((int) request('departamento_id') === $departamento->id)>
                        {{ $departamento->nombre }}
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
            <x-stat-card label="Empleados" :value="$empleados->count()" icon="people" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Nomina mensual" :value="'$'.number_format($nomina, 2)" icon="cash-stack" />
        </div>
    </div>

    <x-card>
        <x-table :head="['Numero', 'Empleado', 'Departamento', 'Puesto', 'Contratacion', 'Tipo', ['label' => 'Sueldo base', 'align' => 'end'], 'Estado']">
            @forelse ($empleados as $empleado)
                <tr>
                    <td class="text-muted">{{ $empleado->numero_empleado ?? '--' }}</td>
                    <td>{{ $empleado->nombre_completo }}</td>
                    <td>{{ $empleado->departamento?->nombre ?? '--' }}</td>
                    <td>{{ $empleado->puesto?->nombre ?? '--' }}</td>
                    <td>{{ $empleado->fecha_contratacion?->format('d/m/Y') }}</td>
                    <td>{{ $empleado->tipo_contrato?->label() ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $empleado->sueldo_base, 2) }}</td>
                    <td><x-badge :estado="$empleado->estado->color()" :label="$empleado->estado->label()" /></td>
                </tr>
            @empty
                <x-empty :colspan="8" message="Sin empleados para los filtros elegidos." icon="people" />
            @endforelse
        </x-table>
    </x-card>
@endsection
