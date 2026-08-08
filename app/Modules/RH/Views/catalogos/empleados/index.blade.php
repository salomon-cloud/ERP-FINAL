@extends('layouts.app')

@section('title', 'Empleados')
@section('header', 'Empleados')
@section('subtitle', 'Expediente del personal')

@section('content')
    <x-page-header title="Empleados" subtitle="RH / Empleados">
        <a class="btn btn-primary" href="{{ route('rh.empleados.create') }}">
            <i class="bi bi-person-plus me-1"></i>Nuevo
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Nombre, numero, RFC, CURP o correo...">
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

    <x-card>
        <x-table :head="['Numero', 'Empleado', 'Departamento', 'Puesto', 'Jefe', ['label' => 'Sueldo base', 'align' => 'end'], 'Estado', '']">
            @forelse ($empleados as $empleado)
                <tr>
                    <td class="text-muted">{{ $empleado->numero_empleado ?? '--' }}</td>
                    <td>
                        <div class="fw-bold">{{ $empleado->nombre_completo }}</div>
                        <small class="text-muted">{{ $empleado->correo }}</small>
                    </td>
                    <td>{{ $empleado->departamento?->nombre ?? '--' }}</td>
                    <td>{{ $empleado->puesto?->nombre ?? '--' }}</td>
                    <td>{{ $empleado->jefe?->nombre_completo ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $empleado->sueldo_base, 2) }}</td>
                    <td><x-badge :estado="$empleado->estado->color()" :label="$empleado->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.empleados.show', $empleado) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.empleados.edit', $empleado) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.empleados.destroy', $empleado) }}"
                              data-confirm="Estas seguro de dar de baja a {{ $empleado->nombre_completo }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay empleados registrados." icon="people" />
            @endforelse
        </x-table>

        {{ $empleados->links() }}
    </x-card>
@endsection
