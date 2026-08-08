@extends('layouts.app')

@section('title', 'Plantilla por departamento')
@section('header', 'Plantilla por departamento')

@section('content')
    <x-page-header title="Plantilla por departamento" subtitle="RH / Reportes / Plantilla por departamento">
        @include('rh::paginas.reportes._acciones', ['ruta' => 'rh.reportes.plantilla-por-departamento'])
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-stat-card label="Empleados activos" :value="$totalEmpleados" icon="people" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Nomina mensual" :value="'$'.number_format($totalNomina, 2)" icon="cash-stack" />
        </div>
    </div>

    <x-card>
        <x-table :head="['Departamento', ['label' => 'Empleados', 'align' => 'end'], ['label' => 'Nomina mensual', 'align' => 'end'], ['label' => 'Sueldo promedio', 'align' => 'end'], ['label' => '% de la nomina', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="fw-bold">{{ $fila->departamento }}</td>
                    <td class="text-end">{{ $fila->empleados }}</td>
                    <td class="text-end">${{ number_format((float) $fila->nomina, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $fila->promedio, 2) }}</td>
                    <td class="text-end">
                        {{ $totalNomina > 0 ? number_format((float) $fila->nomina / $totalNomina * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay empleados activos." icon="people" />
            @endforelse
        </x-table>
    </x-card>
@endsection
