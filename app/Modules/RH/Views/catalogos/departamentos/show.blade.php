@extends('layouts.app')

@section('title', $departamento->nombre)
@section('header', $departamento->nombre)

@section('content')
    <x-page-header :title="$departamento->nombre" subtitle="RH / Departamentos / Detalle">
        <a class="btn btn-outline-primary" href="{{ route('rh.departamentos.edit', $departamento) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-card title="Datos">
                <dl class="row mb-0">
                    <dt class="col-5">Codigo</dt><dd class="col-7">{{ $departamento->codigo ?? '--' }}</dd>
                    <dt class="col-5">Depende de</dt><dd class="col-7">{{ $departamento->padre?->nombre ?? '--' }}</dd>
                    <dt class="col-5">Jefe</dt><dd class="col-7">{{ $departamento->jefe?->nombre_completo ?? '--' }}</dd>
                    <dt class="col-5">Responsable</dt><dd class="col-7">{{ $departamento->responsable ?? '--' }}</dd>
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7"><x-badge :estado="$departamento->estado->color()" :label="$departamento->estado->label()" /></dd>
                    <dt class="col-5">Descripcion</dt><dd class="col-7">{{ $departamento->descripcion ?? '--' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Departamentos que dependen de este" class="mb-3">
                <x-table :head="['Nombre', 'Estado']">
                    @forelse ($departamento->hijos as $hijo)
                        <tr>
                            <td><a href="{{ route('rh.departamentos.show', $hijo) }}">{{ $hijo->nombre }}</a></td>
                            <td><x-badge :estado="$hijo->estado->color()" :label="$hijo->estado->label()" /></td>
                        </tr>
                    @empty
                        <x-empty :colspan="2" message="No tiene departamentos hijos." icon="diagram-3" />
                    @endforelse
                </x-table>
            </x-card>

            <x-card title="Puestos">
                <x-table :head="['Puesto', ['label' => 'Sueldo minimo', 'align' => 'end'], ['label' => 'Sueldo maximo', 'align' => 'end']]">
                    @forelse ($departamento->puestos as $puesto)
                        <tr>
                            <td><a href="{{ route('rh.puestos.show', $puesto) }}">{{ $puesto->nombre }}</a></td>
                            <td class="text-end">${{ number_format((float) $puesto->sueldo_minimo, 2) }}</td>
                            <td class="text-end">
                                {{ (float) $puesto->sueldo_maximo > 0 ? '$'.number_format((float) $puesto->sueldo_maximo, 2) : 'Sin tope' }}
                            </td>
                        </tr>
                    @empty
                        <x-empty :colspan="3" message="No tiene puestos registrados." icon="briefcase" />
                    @endforelse
                </x-table>
            </x-card>
        </div>
    </div>
@endsection
