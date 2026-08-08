@extends('layouts.app')

@section('title', 'Solicitud')
@section('header', 'Solicitud de permiso')

@section('content')
    <x-page-header :title="$permiso->empleado?->nombre_completo ?? 'Solicitud'" subtitle="RH / Permisos / Detalle" />

    <div class="row g-3">
        <div class="col-lg-7">
            <x-card title="Datos de la solicitud">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Empleado</dt><dd class="col-sm-8">{{ $permiso->empleado?->nombre_completo ?? '--' }}</dd>
                    <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">{{ $permiso->tipo->label() }}</dd>
                    <dt class="col-sm-4">Periodo</dt>
                    <dd class="col-sm-8">
                        {{ $permiso->fecha_inicio->format('d/m/Y') }} - {{ $permiso->fecha_fin->format('d/m/Y') }}
                    </dd>
                    <dt class="col-sm-4">Dias</dt><dd class="col-sm-8">{{ (float) $permiso->dias }}</dd>
                    <dt class="col-sm-4">Goce de sueldo</dt>
                    <dd class="col-sm-8">{{ $permiso->con_goce ? 'Con goce' : 'Sin goce (se descuenta en nomina)' }}</dd>
                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8"><x-badge :estado="$permiso->estado->color()" :label="$permiso->estado->label()" /></dd>
                    <dt class="col-sm-4">Motivo</dt><dd class="col-sm-8">{{ $permiso->motivo }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-lg-5">
            @if ($permiso->estado->esFinal())
                <x-card title="Revision">
                    <dl class="row mb-0">
                        <dt class="col-5">Decision</dt>
                        <dd class="col-7"><x-badge :estado="$permiso->estado->color()" :label="$permiso->estado->label()" /></dd>
                        <dt class="col-5">Reviso</dt><dd class="col-7">{{ $permiso->revisadoPor?->name ?? '--' }}</dd>
                        <dt class="col-5">Fecha</dt><dd class="col-7">{{ $permiso->revisado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                        <dt class="col-5">Comentario</dt><dd class="col-7">{{ $permiso->comentario_revision ?? '--' }}</dd>
                    </dl>
                </x-card>
            @else
                <x-card title="Revisar solicitud" subtitle="Aprobar marca los dias en asistencia">
                    <form method="POST" action="{{ route('rh.permisos.revisar', $permiso) }}">
                        @csrf @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label" for="comentario_revision">Comentario</label>
                            <textarea class="form-control @error('comentario_revision') is-invalid @enderror"
                                      id="comentario_revision" name="comentario_revision" rows="3"
                                      maxlength="500">{{ old('comentario_revision') }}</textarea>
                            @error('comentario_revision') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit" name="estado" value="aprobado">
                                <i class="bi bi-check-lg me-1"></i>Aprobar
                            </button>
                            <button class="btn btn-outline-danger" type="submit" name="estado" value="rechazado">
                                <i class="bi bi-x-lg me-1"></i>Rechazar
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
@endsection
