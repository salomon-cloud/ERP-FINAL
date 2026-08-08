@extends('layouts.app')

@section('title', 'Organigrama')
@section('header', 'Organigrama')
@section('subtitle', 'Estructura de departamentos y linea de mando')

@section('content')
    <x-page-header title="Organigrama" subtitle="RH / Organigrama">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-card title="Departamentos" subtitle="Jerarquia por departamento padre">
                @if ($raicesDepartamento->isEmpty())
                    <x-empty message="No hay departamentos activos." icon="building" />
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach ($raicesDepartamento as $departamento)
                            @include('rh::paginas.organigrama._departamento', [
                                'departamento' => $departamento,
                                'departamentosPorPadre' => $departamentosPorPadre,
                                'empleadosPorDepartamento' => $empleadosPorDepartamento,
                            ])
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card title="Linea de mando" subtitle="Quien reporta a quien">
                @if ($sinJefe->isEmpty())
                    <x-empty message="No hay empleados activos." icon="people" />
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach ($sinJefe as $empleado)
                            @include('rh::paginas.organigrama._rama', [
                                'empleado' => $empleado,
                                'empleadosPorJefe' => $empleadosPorJefe,
                            ])
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
