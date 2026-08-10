@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="30" required value="{{ old('codigo', $categoria->codigo) }}">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="150" required value="{{ old('nombre', $categoria->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="padre_id">Categoria padre</label>
        <select class="form-select @error('padre_id') is-invalid @enderror" id="padre_id" name="padre_id">
            <option value="">Es una categoria raiz</option>
            @foreach ($padres as $padre)
                <option value="{{ $padre->id }}" @selected((int) old('padre_id', $categoria->padre_id) === $padre->id)>
                    {{ $padre->nombre }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Filtrar por una categoria trae tambien sus subcategorias.</small>
        @error('padre_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="descripcion">Descripcion</label>
        <textarea class="form-control" id="descripcion" name="descripcion" rows="2">{{ old('descripcion', $categoria->descripcion) }}</textarea>
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo"
                   @checked(old('activo', $categoria->exists ? $categoria->activo : true))>
            <label class="form-check-label" for="activo">Activa</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.categorias.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
