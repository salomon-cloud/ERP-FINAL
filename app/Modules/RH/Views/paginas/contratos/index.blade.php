@extends('layouts.app')

@section('title', 'Contratos')
@section('header', 'Contratos')

@section('content')
    <x-page-header title="Contratos" subtitle="RH / Contratos">
        <a class="btn btn-primary" href="{{ route('rh.contratos.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo</a>
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
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-select" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filtro-vencer">Vigencia</label>
            <select class="form-select" id="filtro-vencer" name="por_vencer">
                <option value="">Todas</option>
                <option value="1" @selected(request('por_vencer') === '1')>Por vencer (30 dias)</option>
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <x-table :head="['Numero', 'Empleado', 'Tipo', 'Vigencia', ['label' => 'Sueldo', 'align' => 'end'], 'Estado', '']">
            @forelse ($contratos as $contrato)
                <tr>
                    <td class="text-muted">{{ $contrato->numero_contrato ?? '--' }}</td>
                    <td>{{ $contrato->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $contrato->tipo_contrato->label() }}</td>
                    <td class="small">
                        {{ $contrato->fecha_inicio->format('d/m/Y') }} -
                        {{ $contrato->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}
                    </td>
                    <td class="text-end">${{ number_format((float) $contrato->sueldo, 2) }}</td>
                    <td><x-badge :estado="$contrato->estado->color()" :label="$contrato->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.contratos.show', $contrato) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.contratos.edit', $contrato) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.contratos.destroy', $contrato) }}"
                              data-confirm="Estas seguro de eliminar este contrato?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay contratos registrados." icon="file-earmark-text" />
            @endforelse
        </x-table>

        {{ $contratos->links() }}
    </x-card>
@endsection
