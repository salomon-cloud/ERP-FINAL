@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="proveedor_id">Proveedor</label>
        <select class="form-select @error('proveedor_id') is-invalid @enderror" id="proveedor_id" name="proveedor_id" required>
            <option value="">Selecciona...</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $orden->proveedor_id) === $proveedor->id)>
                    {{ $proveedor->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $orden->fecha?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha_entrega">Entrega</label>
        <input class="form-control @error('fecha_entrega') is-invalid @enderror" type="date"
               id="fecha_entrega" name="fecha_entrega"
               value="{{ old('fecha_entrega', $orden->fecha_entrega?->toDateString()) }}">
        @error('fecha_entrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="moneda">Moneda</label>
        <input class="form-control @error('moneda') is-invalid @enderror" type="text" id="moneda" name="moneda"
               maxlength="3" required value="{{ old('moneda', $orden->moneda ?? 'MXN') }}">
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control" id="notas" name="notas" rows="2">{{ old('notas', $orden->notas) }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('compras.ordenes.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
