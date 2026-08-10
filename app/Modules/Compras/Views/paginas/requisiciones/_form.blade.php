@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="departamento_id">Departamento que solicita</label>
        <select class="form-select @error('departamento_id') is-invalid @enderror" id="departamento_id" name="departamento_id">
            <option value="">Sin departamento</option>
            @foreach ($departamentos as $departamento)
                <option value="{{ $departamento->id }}" @selected((int) old('departamento_id', $requisicion->departamento_id) === $departamento->id)>
                    {{ $departamento->nombre }}
                </option>
            @endforeach
        </select>
        @error('departamento_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_requerida">Fecha requerida</label>
        <input class="form-control @error('fecha_requerida') is-invalid @enderror" type="date"
               id="fecha_requerida" name="fecha_requerida"
               value="{{ old('fecha_requerida', $requisicion->fecha_requerida?->toDateString()) }}">
        @error('fecha_requerida') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control" id="notas" name="notas" rows="2">{{ old('notas', $requisicion->notas) }}</textarea>
        <small class="text-muted">Para que se necesita, o cualquier cosa que ayude a quien autoriza.</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('compras.requisiciones.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
