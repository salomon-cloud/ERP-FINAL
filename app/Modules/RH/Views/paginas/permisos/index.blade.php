@extends('layouts.app')

@section('title', 'Permisos y vacaciones')
@section('header', 'Permisos y vacaciones')
@section('subtitle', 'Solicitudes del personal')

@section('content')
    <x-page-header title="Permisos y vacaciones" subtitle="RH / Permisos">
        <a class="btn btn-primary" href="{{ route('rh.permisos.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
    </x-page-header>

    <x-filter-bar placeholder="Filtra con los selectores...">
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
            <label class="form-label" for="filtro-tipo">Tipo</label>
            <select class="form-select" id="filtro-tipo" name="tipo">
                <option value="">Todos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('tipo') === $valor)>{{ $etiqueta }}</option>
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
        <x-table :head="['Empleado', 'Tipo', 'Periodo', ['label' => 'Dias', 'align' => 'end'], 'Goce', 'Estado', 'Reviso', '']">
            @forelse ($permisos as $permiso)
                <tr>
                    <td>{{ $permiso->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $permiso->tipo->label() }}</td>
                    <td class="small">
                        {{ $permiso->fecha_inicio->format('d/m/Y') }} - {{ $permiso->fecha_fin->format('d/m/Y') }}
                    </td>
                    <td class="text-end">{{ (float) $permiso->dias }}</td>
                    <td>{{ $permiso->con_goce ? 'Con goce' : 'Sin goce' }}</td>
                    <td><x-badge :estado="$permiso->estado->color()" :label="$permiso->estado->label()" /></td>
                    <td class="small text-muted">{{ $permiso->revisadoPor?->name ?? '--' }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.permisos.show', $permiso) }}"><i class="bi bi-eye"></i></a>
                        @unless ($permiso->estado->esFinal())
                            <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.permisos.edit', $permiso) }}"><i class="bi bi-pencil"></i></a>
                        @endunless
                        <form class="d-inline" method="POST" action="{{ route('rh.permisos.destroy', $permiso) }}"
                              data-confirm="Estas seguro de eliminar esta solicitud?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay solicitudes registradas." icon="calendar2-week" />
            @endforelse
        </x-table>

        {{ $permisos->links() }}
    </x-card>
@endsection
