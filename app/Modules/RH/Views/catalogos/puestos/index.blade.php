@extends('layouts.app')

@section('title', 'Puestos')
@section('header', 'Puestos')
@section('subtitle', 'Catalogo de puestos por departamento')

@section('content')
    <x-page-header title="Puestos" subtitle="RH / Puestos">
        <a class="btn btn-primary" href="{{ route('rh.puestos.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo</a>
    </x-page-header>

    <x-filter-bar placeholder="Nombre o codigo...">
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
        <x-table :head="['Codigo', 'Puesto', 'Departamento', ['label' => 'Sueldo minimo', 'align' => 'end'], ['label' => 'Sueldo maximo', 'align' => 'end'], ['label' => 'Empleados', 'align' => 'end'], 'Estado', '']">
            @forelse ($puestos as $puesto)
                <tr>
                    <td class="text-muted">{{ $puesto->codigo ?? '--' }}</td>
                    <td class="fw-bold">{{ $puesto->nombre }}</td>
                    <td>{{ $puesto->departamento?->nombre ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $puesto->sueldo_minimo, 2) }}</td>
                    <td class="text-end">
                        {{ (float) $puesto->sueldo_maximo > 0 ? '$'.number_format((float) $puesto->sueldo_maximo, 2) : 'Sin tope' }}
                    </td>
                    <td class="text-end">{{ $puesto->empleados_count }}</td>
                    <td><x-badge :estado="$puesto->estado->color()" :label="$puesto->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.puestos.show', $puesto) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.puestos.edit', $puesto) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.puestos.destroy', $puesto) }}"
                              data-confirm="Estas seguro de eliminar el puesto {{ $puesto->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay puestos registrados." icon="briefcase" />
            @endforelse
        </x-table>

        {{ $puestos->links() }}
    </x-card>
@endsection
