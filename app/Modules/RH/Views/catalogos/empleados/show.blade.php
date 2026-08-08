@extends('layouts.app')

@section('title', $empleado->nombre_completo)
@section('header', $empleado->nombre_completo)

@section('content')
    <x-page-header :title="$empleado->nombre_completo" subtitle="RH / Empleados / Expediente">
        <a class="btn btn-outline-primary" href="{{ route('rh.empleados.edit', $empleado) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card title="Identificacion">
                @if ($empleado->fotografia)
                    <img class="img-fluid rounded mb-3" src="{{ asset('storage/'.$empleado->fotografia) }}"
                         alt="{{ $empleado->nombre_completo }}">
                @endif

                <dl class="row mb-0">
                    <dt class="col-5">Numero</dt><dd class="col-7">{{ $empleado->numero_empleado ?? '--' }}</dd>
                    <dt class="col-5">CURP</dt><dd class="col-7">{{ $empleado->curp }}</dd>
                    <dt class="col-5">RFC</dt><dd class="col-7">{{ $empleado->rfc }}</dd>
                    <dt class="col-5">NSS</dt><dd class="col-7">{{ $empleado->nss ?? '--' }}</dd>
                    <dt class="col-5">Genero</dt><dd class="col-7">{{ $empleado->genero?->label() ?? '--' }}</dd>
                    <dt class="col-5">Nacimiento</dt><dd class="col-7">{{ $empleado->fecha_nacimiento?->format('d/m/Y') }}</dd>
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7"><x-badge :estado="$empleado->estado->color()" :label="$empleado->estado->label()" /></dd>
                </dl>
            </x-card>
        </div>

        <div class="col-lg-8">
            <x-card title="Puesto y nomina" class="mb-3">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Departamento</dt><dd class="col-sm-8">{{ $empleado->departamento?->nombre ?? '--' }}</dd>
                    <dt class="col-sm-4">Puesto</dt><dd class="col-sm-8">{{ $empleado->puesto?->nombre ?? '--' }}</dd>
                    <dt class="col-sm-4">Jefe directo</dt><dd class="col-sm-8">{{ $empleado->jefe?->nombre_completo ?? '--' }}</dd>
                    <dt class="col-sm-4">Contratacion</dt><dd class="col-sm-8">{{ $empleado->fecha_contratacion?->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Tipo de contrato</dt><dd class="col-sm-8">{{ $empleado->tipo_contrato?->label() ?? '--' }}</dd>
                    <dt class="col-sm-4">Sueldo base</dt>
                    <dd class="col-sm-8">${{ number_format((float) $empleado->sueldo_base, 2) }} {{ $empleado->moneda }}</dd>
                    <dt class="col-sm-4">Frecuencia de pago</dt><dd class="col-sm-8">{{ $empleado->frecuencia_pago?->label() ?? '--' }}</dd>
                    <dt class="col-sm-4">Banco</dt>
                    <dd class="col-sm-8">{{ $empleado->banco ?? '--' }} {{ $empleado->cuenta_bancaria }}</dd>
                    @if ($empleado->fecha_baja)
                        <dt class="col-sm-4">Baja</dt>
                        <dd class="col-sm-8">{{ $empleado->fecha_baja->format('d/m/Y') }} - {{ $empleado->motivo_baja }}</dd>
                    @endif
                </dl>
            </x-card>

            <x-card title="Contratos" class="mb-3">
                <x-table :head="['Numero', 'Tipo', 'Vigencia', ['label' => 'Sueldo', 'align' => 'end'], 'Estado']">
                    @forelse ($empleado->contratos as $contrato)
                        <tr>
                            <td>{{ $contrato->numero_contrato ?? '--' }}</td>
                            <td>{{ $contrato->tipo_contrato->label() }}</td>
                            <td class="small">
                                {{ $contrato->fecha_inicio->format('d/m/Y') }} -
                                {{ $contrato->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}
                            </td>
                            <td class="text-end">${{ number_format((float) $contrato->sueldo, 2) }}</td>
                            <td><x-badge :estado="$contrato->estado->color()" :label="$contrato->estado->label()" /></td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="Sin contratos registrados." icon="file-earmark-text" />
                    @endforelse
                </x-table>
            </x-card>

            <x-card title="Expediente digital">
                <x-table :head="['Documento', 'Tipo', 'Vigencia', 'Estado']">
                    @forelse ($empleado->documentos as $documento)
                        <tr>
                            <td>{{ $documento->titulo }}</td>
                            <td>{{ $documento->tipo_documento->label() }}</td>
                            <td>{{ $documento->vigencia?->format('d/m/Y') ?? '--' }}</td>
                            <td><x-badge :estado="$documento->estado->color()" :label="$documento->estado->label()" /></td>
                        </tr>
                    @empty
                        <x-empty :colspan="4" message="Sin documentos en el expediente." icon="folder2-open" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>
@endsection
