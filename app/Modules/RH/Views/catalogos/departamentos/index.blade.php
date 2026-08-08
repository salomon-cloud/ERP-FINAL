@extends('layouts.app')

@section('title', 'Departamentos')
@section('header', 'Departamentos')
@section('subtitle', 'Estructura organizacional')

@section('content')
    <x-page-header title="Departamentos" subtitle="RH / Departamentos">
        <a class="btn btn-primary" href="{{ route('rh.departamentos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Nombre, codigo o responsable...">
        <div class="col-md-3">
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
        <x-table :head="['Codigo', 'Nombre', 'Depende de', 'Jefe', ['label' => 'Empleados', 'align' => 'end'], 'Estado', '']">
            @forelse ($departamentos as $departamento)
                <tr>
                    <td class="text-muted">{{ $departamento->codigo ?? '--' }}</td>
                    <td class="fw-bold">{{ $departamento->nombre }}</td>
                    <td>{{ $departamento->padre?->nombre ?? '--' }}</td>
                    <td>{{ $departamento->jefe?->nombre_completo ?? '--' }}</td>
                    <td class="text-end">{{ $departamento->empleados_count }}</td>
                    <td><x-badge :estado="$departamento->estado->color()" :label="$departamento->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.departamentos.show', $departamento) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.departamentos.edit', $departamento) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.departamentos.destroy', $departamento) }}"
                              data-confirm="Estas seguro de eliminar el departamento {{ $departamento->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay departamentos registrados." icon="building" />
            @endforelse
        </x-table>

        {{ $departamentos->links() }}
    </x-card>
@endsection
