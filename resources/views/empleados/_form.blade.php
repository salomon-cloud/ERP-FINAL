@csrf
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $empleado->nombre) }}" required></div>
    <div class="col-md-6"><label class="form-label">Apellidos</label><input name="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos', $empleado->apellidos) }}" required></div>
    <div class="col-md-4"><label class="form-label">CURP</label><input name="curp" maxlength="18" class="form-control @error('curp') is-invalid @enderror" value="{{ old('curp', $empleado->curp) }}" required></div>
    <div class="col-md-4"><label class="form-label">RFC</label><input name="rfc" maxlength="13" class="form-control @error('rfc') is-invalid @enderror" value="{{ old('rfc', $empleado->rfc) }}" required></div>
    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" name="correo" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo', $empleado->correo) }}" required></div>
    <div class="col-md-4"><label class="form-label">Telefono</label><input name="telefono" class="form-control" value="{{ old('telefono', $empleado->telefono) }}"></div>
    <div class="col-md-4"><label class="form-label">Fecha nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', optional($empleado->fecha_nacimiento)->format('Y-m-d')) }}" required></div>
    <div class="col-md-4"><label class="form-label">Fecha contratacion</label><input type="date" name="fecha_contratacion" class="form-control" value="{{ old('fecha_contratacion', optional($empleado->fecha_contratacion)->format('Y-m-d')) }}" required></div>
    <div class="col-md-4"><label class="form-label">Departamento</label><select name="departamento_id" class="form-select" required><option value="">Seleccionar</option>@foreach($departamentos as $departamento)<option value="{{ $departamento->id }}" @selected(old('departamento_id', $empleado->departamento_id)==$departamento->id)>{{ $departamento->nombre }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Puesto</label><select name="puesto_id" class="form-select" required><option value="">Seleccionar</option>@foreach($puestos as $puesto)<option value="{{ $puesto->id }}" @selected(old('puesto_id', $empleado->puesto_id)==$puesto->id)>{{ $puesto->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Sueldo base</label><input type="number" step="0.01" name="sueldo_base" class="form-control" value="{{ old('sueldo_base', $empleado->sueldo_base) }}" required></div>
    <div class="col-md-2"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="activo" @selected(old('estado', $empleado->estado ?: 'activo')==='activo')>Activo</option><option value="inactivo" @selected(old('estado', $empleado->estado)==='inactivo')>Inactivo</option></select></div>
    <div class="col-md-8"><label class="form-label">Direccion</label><textarea name="direccion" class="form-control" rows="2">{{ old('direccion', $empleado->direccion) }}</textarea></div>
    <div class="col-md-4"><label class="form-label">Fotografia</label><input type="file" name="fotografia" class="form-control" accept="image/*"></div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
</div>
