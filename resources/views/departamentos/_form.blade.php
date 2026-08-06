@csrf
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" value="{{ old('nombre',$departamento->nombre) }}" required></div>
    <div class="col-md-4"><label class="form-label">Responsable</label><input name="responsable" class="form-control" value="{{ old('responsable',$departamento->responsable) }}"></div>
    <div class="col-md-2"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="activo" @selected(old('estado',$departamento->estado ?: 'activo')==='activo')>Activo</option><option value="inactivo" @selected(old('estado',$departamento->estado)==='inactivo')>Inactivo</option></select></div>
    <div class="col-12"><label class="form-label">Descripcion</label><textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion',$departamento->descripcion) }}</textarea></div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('departamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></div>
