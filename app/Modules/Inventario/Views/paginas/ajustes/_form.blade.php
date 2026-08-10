@csrf

<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label" for="motivo">Motivo del ajuste</label>
        <textarea class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo"
                  rows="2" maxlength="500" required>{{ old('motivo', $ajuste->motivo) }}</textarea>
        <small class="text-muted">
            Es obligatorio y queda en la bitacora: un ajuste sin explicacion es un descuadre sin explicacion.
        </small>
        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.ajustes.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
