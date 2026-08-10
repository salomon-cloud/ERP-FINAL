@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="cliente_id">Cliente</label>
        <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
            <option value="">Selecciona...</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((int) old('cliente_id', $factura->cliente_id) === $cliente->id)>
                    {{ $cliente->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="pedido_id">Pedido</label>
        <select class="form-select" id="pedido_id" name="pedido_id">
            <option value="">Sin pedido (venta directa)</option>
            @foreach ($pedidos as $pedido)
                <option value="{{ $pedido->id }}" @selected((int) old('pedido_id', $factura->pedido_id) === $pedido->id)>
                    {{ $pedido->numero_pedido }} - {{ $pedido->cliente?->nombre }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Para facturar lo surtido de un pedido, usa el boton "Facturar" desde el pedido.</small>
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
        <label class="form-label" for="fecha_emision">Emision</label>
        <input class="form-control @error('fecha_emision') is-invalid @enderror" type="date"
               id="fecha_emision" name="fecha_emision" required
               value="{{ old('fecha_emision', $factura->fecha_emision?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha_emision') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_vencimiento">Vencimiento</label>
        <input class="form-control @error('fecha_vencimiento') is-invalid @enderror" type="date"
               id="fecha_vencimiento" name="fecha_vencimiento"
               value="{{ old('fecha_vencimiento', $factura->fecha_vencimiento?->toDateString()) }}">
        @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="condicion_pago_id">Condicion de pago</label>
        <select class="form-select" id="condicion_pago_id" name="condicion_pago_id">
            <option value="">Sin definir</option>
            @foreach ($condicionesPago as $condicion)
                <option value="{{ $condicion->id }}" @selected((int) old('condicion_pago_id', $factura->condicion_pago_id) === $condicion->id)>
                    {{ $condicion->nombre }}
                </option>
            @endforeach
        </select>
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
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('ventas.facturas.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
