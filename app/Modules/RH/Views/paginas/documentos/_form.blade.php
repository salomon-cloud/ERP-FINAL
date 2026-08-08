@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $documento->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-7">
        <label class="form-label" for="titulo">Titulo</label>
        <input class="form-control @error('titulo') is-invalid @enderror" type="text" id="titulo" name="titulo"
               required maxlength="200" value="{{ old('titulo', $documento->titulo) }}">
        @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="tipo_documento">Tipo de documento</label>
        <select class="form-select @error('tipo_documento') is-invalid @enderror" id="tipo_documento"
                name="tipo_documento" required>
            @foreach ($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo_documento', $documento->tipo_documento?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="vigencia">Vigencia</label>
        <input class="form-control @error('vigencia') is-invalid @enderror" type="date" id="vigencia" name="vigencia"
               value="{{ old('vigencia', $documento->vigencia?->format('Y-m-d')) }}">
        <small class="text-muted">Vacio si no caduca.</small>
        @error('vigencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $documento->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="alert alert-light border mb-0">
            <i class="bi bi-info-circle me-1"></i>
            El archivo se sube por el modulo de adjuntos compartido. Aqui solo se registra el dato del expediente.
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.documentos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
