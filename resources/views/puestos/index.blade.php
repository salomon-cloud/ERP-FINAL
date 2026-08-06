@extends('layouts.app')
@section('title', 'Puestos')
@section('header', 'Puestos')
@section('content')
<div class="soft-card p-3">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <form class="d-flex flex-wrap gap-2" method="GET"><input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar puesto"><select class="form-select" name="departamento_id"><option value="">Todos</option>@foreach($departamentos as $departamento)<option value="{{ $departamento->id }}" @selected(request('departamento_id')==$departamento->id)>{{ $departamento->nombre }}</option>@endforeach</select><button class="btn btn-outline-primary"><i class="bi bi-filter"></i></button></form>
        <a href="{{ route('puestos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo puesto</a>
    </div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Puesto</th><th>Departamento</th><th>Rango sueldo</th><th>Empleados</th><th>Estado</th><th></th></tr></thead><tbody>
    @forelse($puestos as $puesto)
        <tr><td><strong>{{ $puesto->nombre }}</strong><br><small class="text-muted">{{ $puesto->descripcion }}</small></td><td>{{ $puesto->departamento->nombre }}</td><td>${{ number_format($puesto->sueldo_minimo,2) }} - ${{ number_format($puesto->sueldo_maximo,2) }}</td><td>{{ $puesto->empleados_count }}</td><td>@include('partials.badge',['estado'=>$puesto->estado])</td><td class="text-end"><a class="btn btn-sm btn-outline-info action-btn" href="{{ route('puestos.show',$puesto) }}"><i class="bi bi-eye"></i></a> <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('puestos.edit',$puesto) }}"><i class="bi bi-pencil"></i></a> <form class="d-inline" method="POST" action="{{ route('puestos.destroy',$puesto) }}" data-confirm="Eliminar puesto?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button></form></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted">Sin puestos.</td></tr>@endforelse
    </tbody></table></div>{{ $puestos->links() }}
</div>
@endsection
