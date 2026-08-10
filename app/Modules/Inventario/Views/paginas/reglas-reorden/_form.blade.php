@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="producto_id">Producto</label>
        <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
            <option value="">Selecciona...</option>
            @foreach ($productos as $producto)
                <option value="{{ $producto->id }}" @selected((int) old('producto_id', $regla->producto_id) === $producto->id)>
                    {{ $producto->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="almacen_id">Almacen</label>
        <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id" required>
            <option value="">Selecciona...</option>
            @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->id }}" @selected((int) old('almacen_id', $regla->almacen_id) === $almacen->id)>
                    {{ $almacen->codigo }} - {{ $almacen->nombre }}
                </option>
            @endforeach
        </select>
        @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="cantidad_minima">Cantidad minima</label>
        <input class="form-control @error('cantidad_minima') is-invalid @enderror" type="number" step="0.000001" min="0"
               id="cantidad_minima" name="cantidad_minima" required value="{{ old('cantidad_minima', (float) ($regla->cantidad_minima ?? 0)) }}">
        <small class="text-muted">Por debajo de esto se avisa.</small>
        @error('cantidad_minima') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="cantidad_maxima">Cantidad maxima</label>
        <input class="form-control @error('cantidad_maxima') is-invalid @enderror" type="number" step="0.000001" min="0"
               id="cantidad_maxima" name="cantidad_maxima" required value="{{ old('cantidad_maxima', (float) ($regla->cantidad_maxima ?? 0)) }}">
        <small class="text-muted">Usa 0 si no hay tope.</small>
        @error('cantidad_maxima') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="cantidad_reorden">Cantidad a reordenar</label>
        <input class="form-control @error('cantidad_reorden') is-invalid @enderror" type="number" step="0.000001" min="0"
               id="cantidad_reorden" name="cantidad_reorden" value="{{ old('cantidad_reorden', (float) ($regla->cantidad_reorden ?? 0)) }}">
        <small class="text-muted">Lote fijo de compra. En 0, se sugiere lo que falte para el maximo.</small>
        @error('cantidad_reorden') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="dias_entrega">Dias de entrega</label>
        <input class="form-control @error('dias_entrega') is-invalid @enderror" type="number" min="0" max="365"
               id="dias_entrega" name="dias_entrega" value="{{ old('dias_entrega', $regla->dias_entrega ?? 0) }}">
        @error('dias_entrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo"
                   @checked(old('activo', $regla->exists ? $regla->activo : true))>
            <label class="form-check-label" for="activo">Activa</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.reglas-reorden.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
