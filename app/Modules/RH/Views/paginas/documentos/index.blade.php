@extends('layouts.app')

@section('title', 'Expediente digital')
@section('header', 'Expediente digital')

@section('content')
    <x-page-header title="Documentos del expediente" subtitle="RH / Documentos">
        <a class="btn btn-primary" href="{{ route('rh.documentos.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo</a>
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
            <select class="form-select" id="filtro-tipo" name="tipo_documento">
                <option value="">Todos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('tipo_documento') === $valor)>{{ $etiqueta }}</option>
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
        <x-table :head="['Documento', 'Empleado', 'Tipo', 'Vigencia', 'Archivo', 'Estado', '']">
            @forelse ($documentos as $documento)
                <tr>
                    <td class="fw-bold">{{ $documento->titulo }}</td>
                    <td>{{ $documento->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $documento->tipo_documento->label() }}</td>
                    <td>{{ $documento->vigencia?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                    <td>
                        @if ($documento->adjunto_id)
                            <span class="text-success"><i class="bi bi-paperclip"></i> Adjunto</span>
                        @else
                            <span class="text-muted">Pendiente de entrega</span>
                        @endif
                    </td>
                    <td><x-badge :estado="$documento->estado->color()" :label="$documento->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.documentos.edit', $documento) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.documentos.destroy', $documento) }}"
                              data-confirm="Estas seguro de eliminar el documento {{ $documento->titulo }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay documentos en el expediente." icon="folder2-open" />
            @endforelse
        </x-table>

        {{ $documentos->links() }}
    </x-card>
@endsection
