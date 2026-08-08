@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="30" value="{{ old('codigo', $puesto->codigo) }}">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               required value="{{ old('nombre', $puesto->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="departamento_id">Departamento</label>
        <select class="form-select @error('departamento_id') is-invalid @enderror" id="departamento_id"
                name="departamento_id" required>
            <option value="">Selecciona...</option>
            @foreach ($departamentos as $departamento)
                <option value="{{ $departamento->id }}" @selected((int) old('departamento_id', $puesto->departamento_id) === $departamento->id)>
                    {{ $departamento->nombre }}
                </option>
            @endforeach
        </select>
        @error('departamento_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="sueldo_minimo">Sueldo minimo</label>
        <input class="form-control @error('sueldo_minimo') is-invalid @enderror" type="number" step="0.01" min="0"
               id="sueldo_minimo" name="sueldo_minimo" required value="{{ old('sueldo_minimo', (float) $puesto->sueldo_minimo) }}">
        @error('sueldo_minimo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="sueldo_maximo">Sueldo maximo</label>
        <input class="form-control @error('sueldo_maximo') is-invalid @enderror" type="number" step="0.01" min="0"
               id="sueldo_maximo" name="sueldo_maximo" required value="{{ old('sueldo_maximo', (float) $puesto->sueldo_maximo) }}">
        <small class="text-muted">Usa 0 si el puesto no tiene tope.</small>
        @error('sueldo_maximo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $puesto->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="descripcion">Descripcion</label>
        <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion"
                  rows="3">{{ old('descripcion', $puesto->descripcion) }}</textarea>
        @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.puestos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
