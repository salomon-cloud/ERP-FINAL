@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="proveedor_id">Proveedor</label>
        <select class="form-select @error('proveedor_id') is-invalid @enderror" id="proveedor_id" name="proveedor_id" required>
            <option value="">Selecciona...</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $factura->proveedor_id) === $proveedor->id)>
                    {{ $proveedor->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="orden_compra_id">Orden de compra</label>
        <select class="form-select @error('orden_compra_id') is-invalid @enderror" id="orden_compra_id" name="orden_compra_id">
            <option value="">Sin orden (gasto suelto)</option>
            @foreach ($ordenes as $orden)
                <option value="{{ $orden->id }}" @selected((int) old('orden_compra_id', $factura->orden_compra_id) === $orden->id)>
                    {{ $orden->numero_orden }} - {{ $orden->proveedor?->nombre }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Con orden se hace el cotejo de tres vias; sin ella no hay contra que cotejar.</small>
        @error('orden_compra_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="periodo_fiscal_id">Periodo fiscal</label>
        <select class="form-select" id="periodo_fiscal_id" name="periodo_fiscal_id">
            <option value="">Sin definir</option>
            @foreach ($periodos as $periodo)
                <option value="{{ $periodo->id }}" @selected((int) old('periodo_fiscal_id', $factura->periodo_fiscal_id) === $periodo->id)>
                    {{ $periodo->nombre }} {{ $periodo->ejercicio }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $factura->fecha?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_vencimiento">Vencimiento</label>
        <input class="form-control @error('fecha_vencimiento') is-invalid @enderror" type="date"
               id="fecha_vencimiento" name="fecha_vencimiento"
               value="{{ old('fecha_vencimiento', $factura->fecha_vencimiento?->toDateString()) }}">
        @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="moneda">Moneda</label>
        <input class="form-control @error('moneda') is-invalid @enderror" type="text" id="moneda" name="moneda"
               maxlength="3" required value="{{ old('moneda', $factura->moneda ?? 'MXN') }}">
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control" id="notas" name="notas" rows="2">{{ old('notas', $factura->notas) }}</textarea>
        <small class="text-muted">Anota aqui el folio que trae la factura del proveedor.</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('compras.facturas.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
