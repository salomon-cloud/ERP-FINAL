@extends('layouts.app')
@section('title', 'Departamentos')
@section('header', 'Departamentos')
@section('content')
<div class="soft-card p-3">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <form class="d-flex gap-2" method="GET"><input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar departamento"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></form>
        <a href="{{ route('departamentos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo departamento</a>
    </div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Responsable</th><th>Empleados</th><th>Puestos</th><th>Estado</th><th></th></tr></thead><tbody>
    @forelse($departamentos as $departamento)
        <tr><td><strong>{{ $departamento->nombre }}</strong><br><small class="text-muted">{{ $departamento->descripcion }}</small></td><td>{{ $departamento->responsable }}</td><td>{{ $departamento->empleados_count }}</td><td>{{ $departamento->puestos_count }}</td><td>@include('partials.badge', ['estado'=>$departamento->estado])</td><td class="text-end"><a class="btn btn-sm btn-outline-info action-btn" href="{{ route('departamentos.show',$departamento) }}"><i class="bi bi-eye"></i></a> <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('departamentos.edit',$departamento) }}"><i class="bi bi-pencil"></i></a> <form class="d-inline" method="POST" action="{{ route('departamentos.destroy',$departamento) }}" data-confirm="Eliminar departamento?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button></form></td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted">Sin departamentos.</td></tr>
    @endforelse
    </tbody></table></div>{{ $departamentos->links() }}
</div>
@endsection
