@extends('layouts.app')

@section('title', 'Reporte de nomina')
@section('header', 'Nomina pagada')

@section('content')
    <x-page-header title="Reporte de nomina" subtitle="RH / Reportes / Nomina">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.nomina'])
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
    </x-filter-bar>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-stat-card label="Recibos" :value="$recibos->count()" icon="receipt" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Percepciones" :value="'$'.number_format($percepciones, 2)" icon="plus-circle" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Deducciones" :value="'$'.number_format($deducciones, 2)" icon="dash-circle" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Neto pagado" :value="'$'.number_format($neto, 2)" icon="cash-stack" />
        </div>
    </div>

    <x-card>
        <x-table :head="['Empleado', 'Periodo', 'Corrida', 'Pago', ['label' => 'Sueldo', 'align' => 'end'], ['label' => 'Bonos', 'align' => 'end'], ['label' => 'ISR', 'align' => 'end'], ['label' => 'IMSS', 'align' => 'end'], ['label' => 'Neto', 'align' => 'end'], 'Estado']">
            @forelse ($recibos as $recibo)
                <tr>
                    <td>{{ $recibo->empleado?->nombre_completo ?? '--' }}</td>
                    <td>{{ $recibo->periodo_pago }}</td>
                    <td class="small text-muted">{{ $recibo->corrida?->numero_corrida ?? 'Suelto' }}</td>
                    <td>{{ $recibo->fecha_pago->format('d/m/Y') }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->sueldo_base, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->bonos, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->isr, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->imss, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $recibo->total_pagar, 2) }}</td>
                    <td><x-badge :estado="$recibo->estado->color()" :label="$recibo->estado->label()" /></td>
                </tr>
            @empty
                <x-empty :colspan="10" message="Sin recibos para los filtros elegidos." icon="receipt" />
            @endforelse
        </x-table>
    </x-card>
@endsection
