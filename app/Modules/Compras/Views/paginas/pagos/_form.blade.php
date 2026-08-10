@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="proveedor_id">Proveedor</label>
        <select class="form-select @error('proveedor_id') is-invalid @enderror" id="proveedor_id" name="proveedor_id" required>
            <option value="">Selecciona...</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $pago->proveedor_id) === $proveedor->id)>
                    {{ $proveedor->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="factura_proveedor_id">Factura</label>
        <select class="form-select @error('factura_proveedor_id') is-invalid @enderror"
                id="factura_proveedor_id" name="factura_proveedor_id">
            <option value="">Pago a cuenta (sin factura)</option>
            @foreach ($facturas as $factura)
                <option value="{{ $factura->id }}" @selected((int) old('factura_proveedor_id', $pago->factura_proveedor_id) === $factura->id)>
                    {{ $factura->numero_factura }} - {{ $factura->proveedor?->nombre }} (saldo ${{ number_format($factura->saldo, 2) }})
                </option>
            @endforeach
        </select>
        @error('factura_proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $pago->fecha?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="monto">Monto</label>
        <input class="form-control @error('monto') is-invalid @enderror" type="number" step="0.01" min="0.01"
               id="monto" name="monto" required value="{{ old('monto', $pago->monto ? (float) $pago->monto : '') }}">
        @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="forma_pago">Forma de pago</label>
        <select class="form-select @error('forma_pago') is-invalid @enderror" id="forma_pago" name="forma_pago" required>
            @foreach ($formasPago as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('forma_pago', $pago->forma_pago?->value ?? 'transferencia') === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('forma_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="cuenta_bancaria_id">Cuenta bancaria</label>
        <select class="form-select" id="cuenta_bancaria_id" name="cuenta_bancaria_id">
            <option value="">Sin definir</option>
            @foreach ($cuentas as $cuenta)
                <option value="{{ $cuenta->id }}" @selected((int) old('cuenta_bancaria_id', $pago->cuenta_bancaria_id) === $cuenta->id)>
                    {{ $cuenta->nombre }} ({{ $cuenta->banco }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-5">
        <label class="form-label" for="referencia">Referencia</label>
        <input class="form-control" type="text" id="referencia" name="referencia" maxlength="120"
               value="{{ old('referencia', $pago->referencia) }}">
        <small class="text-muted">Numero de transferencia, folio de cheque...</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('compras.pagos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
