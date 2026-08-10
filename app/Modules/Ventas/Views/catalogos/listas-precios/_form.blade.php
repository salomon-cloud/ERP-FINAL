@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="30" required value="{{ old('codigo', $lista->codigo) }}">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="150" required value="{{ old('nombre', $lista->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="moneda">Moneda</label>
        <input class="form-control @error('moneda') is-invalid @enderror" type="text" id="moneda" name="moneda"
               maxlength="3" required value="{{ old('moneda', $lista->moneda ?? 'MXN') }}">
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="es_predeterminada" name="es_predeterminada"
                   @checked(old('es_predeterminada', $lista->es_predeterminada))>
            <label class="form-check-label" for="es_predeterminada">Es la lista predeterminada</label>
        </div>
        <small class="text-muted">
            Solo una puede serlo: marcarla aqui desmarca automaticamente a la anterior.
        </small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('ventas.listas-precios.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
