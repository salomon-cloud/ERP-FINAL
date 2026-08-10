@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="almacen_id">Almacen</label>
        <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id" required>
            <option value="">Selecciona...</option>
            @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->id }}" @selected((int) old('almacen_id', $ubicacion->almacen_id) === $almacen->id)>
                    {{ $almacen->codigo }} - {{ $almacen->nombre }}
                </option>
            @endforeach
        </select>
        @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="30" required value="{{ old('codigo', $ubicacion->codigo) }}">
        <small class="text-muted">Unico dentro del almacen.</small>
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="150" required value="{{ old('nombre', $ubicacion->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="d-flex flex-wrap gap-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="es_surtible" name="es_surtible"
                       @checked(old('es_surtible', $ubicacion->exists ? $ubicacion->es_surtible : true))>
                <label class="form-check-label" for="es_surtible">Se puede surtir desde aqui</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo"
                       @checked(old('activo', $ubicacion->exists ? $ubicacion->activo : true))>
                <label class="form-check-label" for="activo">Activa</label>
            </div>
        </div>
        <small class="text-muted">Cuarentena o mercancia danada: deja "surtible" sin marcar.</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.ubicaciones.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
