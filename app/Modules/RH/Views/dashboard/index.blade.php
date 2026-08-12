@extends('layouts.app')

@section('title', 'Recursos Humanos')
@section('header', 'Recursos Humanos')
@section('subtitle', 'Empleados, asistencia, permisos, nomina y organigrama')

@section('content')
    <x-page-header title="Tablero de Recursos Humanos" subtitle="RH / Tablero">
        <a class="btn btn-primary" href="{{ route('rh.empleados.create') }}">
            <i class="bi bi-person-plus me-1"></i>Nuevo empleado
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Empleados activos" :value="$empleadosActivos" icon="people-fill"
                :href="route('rh.empleados.index', ['estado' => 'activo'])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Faltas de hoy" :value="$ausenciasHoy" icon="person-x"
                :href="route('rh.asistencias.index', ['estado' => $estadoFalta, 'desde' => $hoy, 'hasta' => $hoy])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Permisos pendientes" :value="$permisosPendientes" icon="hourglass-split"
                :href="route('rh.permisos.index', ['estado' => $estadoPendiente])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Contratos por vencer" :value="$contratosPorVencer" icon="file-earmark-excel"
                hint="En los proximos 30 dias"
                :href="route('rh.contratos.index', ['por_vencer' => 1])" />
        </div>
    </div>

    <x-card class="mb-3">
        <div class="d-flex flex-wrap gap-2 dashboard-shortcuts">
            <a class="btn btn-outline-primary btn-sm" href="{{ route('rh.asistencias.create') }}">
                <i class="bi bi-calendar-check me-1"></i>Registrar asistencia
            </a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('rh.permisos.create') }}">
                <i class="bi bi-calendar2-plus me-1"></i>Nueva solicitud
            </a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('rh.nomina-corridas.create') }}">
                <i class="bi bi-cash-stack me-1"></i>Nueva corrida de nomina
            </a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('rh.organigrama') }}">
                <i class="bi bi-diagram-3 me-1"></i>Ver organigrama
            </a>
        </div>
    </x-card>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-card title="Solicitudes por revisar" subtitle="Las mas proximas a iniciar">
                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('rh.permisos.index', ['estado' => $estadoPendiente]) }}">Ver todas</a>
                </x-slot:actions>

                <x-table :head="['Empleado', 'Tipo', 'Periodo', ['label' => 'Dias', 'align' => 'end'], '']">
                    @forelse ($solicitudesPendientes as $solicitud)
                        <tr>
                            <td>{{ $solicitud->empleado?->nombre_completo ?? '--' }}</td>
                            <td>{{ $solicitud->tipo->label() }}</td>
                            <td class="small">
                                {{ $solicitud->fecha_inicio->format('d/m/Y') }} -
                                {{ $solicitud->fecha_fin->format('d/m/Y') }}
                            </td>
                            <td class="text-end">{{ (float) $solicitud->dias }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info action-btn"
                                   href="{{ route('rh.permisos.show', $solicitud) }}"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="5" message="No hay solicitudes pendientes." icon="check2-circle" />
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Plantilla por departamento" class="mb-3">
                <x-table :head="['Departamento', ['label' => 'Empleados', 'align' => 'end']]">
                    @forelse ($porDepartamento as $fila)
                        <tr>
                            <td>{{ $fila->departamento }}</td>
                            <td class="text-end fw-bold">{{ $fila->total }}</td>
                        </tr>
                    @empty
                        <x-empty :colspan="2" message="Sin empleados activos." icon="people" />
                    @endforelse
                </x-table>
            </x-card>

            <x-card title="Cumpleanos del mes">
                @forelse ($cumpleanosDelMes as $empleado)
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <a href="{{ route('rh.empleados.show', $empleado) }}">{{ $empleado->nombre_completo }}</a>
                        <span class="text-muted small">{{ $empleado->fecha_nacimiento->format('d/m') }}</span>
                    </div>
                @empty
                    <x-empty message="Nadie cumple anos este mes." icon="cake2" />
                @endforelse
            </x-card>
        </div>
    </div>

    <x-card title="Ultimas corridas de nomina" class="mt-3">
        <x-slot:actions>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('rh.nomina-corridas.index') }}">Ver todas</a>
        </x-slot:actions>

        <x-table :head="['Folio', 'Periodo', 'Estado', ['label' => 'Empleados', 'align' => 'end'], ['label' => 'Neto', 'align' => 'end'], '']">
            @forelse ($ultimasCorridas as $corrida)
                <tr>
                    <td class="fw-bold">{{ $corrida->numero_corrida }}</td>
                    <td>{{ $corrida->periodo?->codigo_periodo ?? '--' }}</td>
                    <td><x-badge :estado="$corrida->estado->color()" :label="$corrida->estado->label()" /></td>
                    <td class="text-end">{{ $corrida->total_empleados }}</td>
                    <td class="text-end">${{ number_format((float) $corrida->total_neto, 2) }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn"
                           href="{{ route('rh.nomina-corridas.show', $corrida) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="Todavia no hay corridas de nomina." icon="cash-stack" />
            @endforelse
        </x-table>
    </x-card>
@endsection
