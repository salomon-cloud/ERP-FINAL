@extends('layouts.app')
@section('title', 'Empleados')
@section('header', 'Empleados')
@section('content')
<div class="soft-card p-3">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <form class="d-flex gap-2" method="GET">
            <input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar empleado, CURP o RFC">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
        @if(auth()->user()->role !== 'Empleado')
            <a href="{{ route('empleados.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo empleado</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Empleado</th><th>CURP</th><th>Departamento</th><th>Puesto</th><th>Sueldo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($empleados as $empleado)
                <tr>
                    <td><strong>{{ $empleado->nombre_completo }}</strong><br><small class="text-muted">{{ $empleado->correo }}</small></td>
                    <td>{{ $empleado->curp }}</td>
                    <td>{{ $empleado->departamento->nombre }}</td>
                    <td>{{ $empleado->puesto->nombre }}</td>
                    <td>${{ number_format($empleado->sueldo_base, 2) }}</td>
                    <td>@include('partials.badge', ['estado' => $empleado->estado])</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('empleados.show', $empleado) }}"><i class="bi bi-eye"></i></a>
                        @if(auth()->user()->role !== 'Empleado')
                            <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('empleados.edit', $empleado) }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('empleados.destroy', $empleado) }}" data-confirm="Eliminar empleado?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No hay empleados registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $empleados->links() }}
</div>
@endsection
