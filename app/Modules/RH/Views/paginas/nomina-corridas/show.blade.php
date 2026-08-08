@extends('layouts.app')

@section('title', $corrida->numero_corrida)
@section('header', 'Corrida '.$corrida->numero_corrida)

@section('content')
    <x-page-header :title="$corrida->numero_corrida"
                   :subtitle="'RH / Nomina / Corridas / '.($corrida->periodo?->codigo_periodo ?? '')">
        @if ($corrida->estado->esEditable())
            <form method="POST" action="{{ route('rh.nomina-corridas.procesar', $corrida) }}">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-gear me-1"></i>Procesar</button>
            </form>
        @endif

        @if ($corrida->estado === \App\Modules\RH\Enums\EstadoNominaCorrida::Procesada)
            <form method="POST" action="{{ route('rh.nomina-corridas.aplicar', $corrida) }}"
                  data-confirm="Aplicar la corrida es definitivo. Continuar?">
                @csrf
                {{-- El bloqueo optimista: se manda la version que se vio en pantalla. --}}
                <input type="hidden" name="version_fila" value="{{ $corrida->version_fila }}">
                <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aplicar</button>
            </form>
        @endif

        @if ($corrida->estado->esCancelable())
            <form method="POST" action="{{ route('rh.nomina-corridas.destroy', $corrida) }}"
                  data-confirm="Estas seguro de cancelar la corrida {{ $corrida->numero_corrida }}?">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
            </form>
        @endif

        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Empleados" :value="$corrida->total_empleados" icon="people" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Percepciones" :value="'$'.number_format((float) $corrida->total_percepciones, 2)" icon="plus-circle" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Deducciones" :value="'$'.number_format((float) $corrida->total_deducciones, 2)" icon="dash-circle" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Neto a pagar" :value="'$'.number_format((float) $corrida->total_neto, 2)" icon="cash-stack" />
        </div>
    </div>

    <x-card title="Datos de la corrida" class="mb-3">
        <dl class="row mb-0">
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-badge :estado="$corrida->estado->color()" :label="$corrida->estado->label()" /></dd>
            <dt class="col-sm-3">Periodo</dt><dd class="col-sm-9">{{ $corrida->periodo?->codigo_periodo ?? '--' }}</dd>
            <dt class="col-sm-3">Generada</dt><dd class="col-sm-9">{{ $corrida->generada_en?->format('d/m/Y H:i') ?? '--' }}</dd>
            <dt class="col-sm-3">Proceso</dt><dd class="col-sm-9">{{ $corrida->procesadaPor?->name ?? '--' }}</dd>
            <dt class="col-sm-3">Aplicada</dt><dd class="col-sm-9">{{ $corrida->aplicada_en?->format('d/m/Y H:i') ?? '--' }}</dd>
            <dt class="col-sm-3">Autorizo</dt><dd class="col-sm-9">{{ $corrida->aprobadaPor?->name ?? '--' }}</dd>
        </dl>
    </x-card>

    <x-card title="Recibos generados">
        <x-table :head="['Empleado', ['label' => 'Sueldo', 'align' => 'end'], ['label' => 'Bonos', 'align' => 'end'], ['label' => 'Ausencias', 'align' => 'end'], ['label' => 'Deducciones', 'align' => 'end'], ['label' => 'ISR', 'align' => 'end'], ['label' => 'IMSS', 'align' => 'end'], ['label' => 'Neto', 'align' => 'end'], 'Estado']">
            @forelse ($recibos as $recibo)
                <tr>
                    <td>{{ $recibo->empleado?->nombre_completo ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->sueldo_base, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->bonos, 2) }}</td>
                    <td class="text-end">{{ (float) $recibo->dias_ausencia }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->deducciones, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->isr, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $recibo->imss, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $recibo->total_pagar, 2) }}</td>
                    <td><x-badge :estado="$recibo->estado->color()" :label="$recibo->estado->label()" /></td>
                </tr>
            @empty
                <x-empty :colspan="9" message="Esta corrida todavia no genera recibos. Procesala para armarlos." icon="receipt" />
            @endforelse
        </x-table>

        {{ $recibos->links() }}
    </x-card>
@endsection
