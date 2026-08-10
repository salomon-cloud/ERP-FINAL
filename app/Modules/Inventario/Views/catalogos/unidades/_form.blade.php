@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="10" required value="{{ old('codigo', $unidad->codigo) }}">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="100" required value="{{ old('nombre', $unidad->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="factor_base">Factor de conversion</label>
        <input class="form-control @error('factor_base') is-invalid @enderror" type="number" step="0.000001" min="0.000001"
               id="factor_base" name="factor_base" required value="{{ old('factor_base', (float) ($unidad->factor_base ?? 1)) }}">
        <small class="text-muted">Cuantas unidades base vale una. PZA = 1, CAJA de 12 = 12.</small>
        @error('factor_base') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="es_base" name="es_base"
                   @checked(old('es_base', $unidad->es_base))>
            <label class="form-check-label" for="es_base">Es la unidad base del sistema</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.unidades.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
