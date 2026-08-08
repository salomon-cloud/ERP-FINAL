@extends('layouts.app')

@section('title', 'Recibos de nomina')
@section('header', 'Recibos de nomina')

@section('content')
    <x-page-header title="Recibos de nomina" subtitle="RH / Nomina / Recibos">
        <a class="btn btn-primary" href="{{ route('rh.nominas.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo recibo</a>
    </x-page-header>

    <x-filter-bar placeholder="Periodo o empleado...">
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

    <x-card>
        <x-table :head="['Empleado', 'Periodo', 'Corrida', 'Pago', ['label' => 'Neto', 'align' => 'end'], 'Estado', '']">
            @forelse ($nominas as $nomina)
                <tr>
                    <td>{{ $nomina->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $nomina->periodo_pago }}</td>
                    <td class="small text-muted">{{ $nomina->corrida?->numero_corrida ?? 'Captura suelta' }}</td>
                    <td>{{ $nomina->fecha_pago->format('d/m/Y') }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $nomina->total_pagar, 2) }}</td>
                    <td><x-badge :estado="$nomina->estado->color()" :label="$nomina->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('rh.nominas.show', $nomina) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('rh.nominas.edit', $nomina) }}"><i class="bi bi-pencil"></i></a>
                        @if ($nomina->estado === \App\Modules\RH\Enums\EstadoNomina::Pendiente)
                            <form class="d-inline" method="POST" action="{{ route('rh.nominas.pagar', $nomina) }}"
                                  data-confirm="Marcar este recibo como pagado?">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success action-btn"><i class="bi bi-cash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay recibos registrados." icon="receipt" />
            @endforelse
        </x-table>

        {{ $nominas->links() }}
    </x-card>
@endsection
