@csrf
<div class="row g-3">
    <div class="col-md-5"><label class="form-label">Nombre del puesto</label><input name="nombre" class="form-control" value="{{ old('nombre',$puesto->nombre) }}" required></div>
    <div class="col-md-5"><label class="form-label">Departamento</label><select name="departamento_id" class="form-select" required><option value="">Seleccionar</option>@foreach($departamentos as $departamento)<option value="{{ $departamento->id }}" @selected(old('departamento_id',$puesto->departamento_id)==$departamento->id)>{{ $departamento->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="activo" @selected(old('estado',$puesto->estado ?: 'activo')==='activo')>Activo</option><option value="inactivo" @selected(old('estado',$puesto->estado)==='inactivo')>Inactivo</option></select></div>
    <div class="col-md-6"><label class="form-label">Sueldo minimo</label><input type="number" step="0.01" name="sueldo_minimo" class="form-control" value="{{ old('sueldo_minimo',$puesto->sueldo_minimo) }}" required></div>
    <div class="col-md-6"><label class="form-label">Sueldo maximo</label><input type="number" step="0.01" name="sueldo_maximo" class="form-control" value="{{ old('sueldo_maximo',$puesto->sueldo_maximo) }}" required></div>
    <div class="col-12"><label class="form-label">Descripcion</label><textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion',$puesto->descripcion) }}</textarea></div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('puestos.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></div>
