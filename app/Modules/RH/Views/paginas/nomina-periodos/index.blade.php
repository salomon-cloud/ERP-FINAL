@extends('layouts.app')

@section('title', 'Periodos de nomina')
@section('header', 'Periodos de nomina')
@section('subtitle', 'El calendario de pago')

@section('content')
    <x-page-header title="Periodos de nomina" subtitle="RH / Nomina / Periodos">
        <a class="btn btn-primary" href="{{ route('rh.nomina-periodos.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo</a>
    </x-page-header>

    <x-filter-bar placeholder="Codigo del periodo...">
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
        <x-table :head="['Codigo', 'Periodo', 'Pago', 'Frecuencia', ['label' => 'Corridas', 'align' => 'end'], 'Estado', '']">
            @forelse ($periodos as $periodo)
                <tr>
                    <td class="fw-bold">{{ $periodo->codigo_periodo }}</td>
                    <td class="small">
                        {{ $periodo->fecha_inicio->format('d/m/Y') }} - {{ $periodo->fecha_fin->format('d/m/Y') }}
                    </td>
                    <td>{{ $periodo->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $periodo->frecuencia->label() }}</td>
                    <td class="text-end">{{ $periodo->corridas_count }}</td>
                    <td><x-badge :estado="$periodo->estado->color()" :label="$periodo->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.nomina-periodos.show', $periodo) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.nomina-periodos.edit', $periodo) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('rh.nomina-periodos.destroy', $periodo) }}"
                              data-confirm="Estas seguro de eliminar el periodo {{ $periodo->codigo_periodo }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay periodos registrados." icon="calendar3" />
            @endforelse
        </x-table>

        {{ $periodos->links() }}
    </x-card>
@endsection
