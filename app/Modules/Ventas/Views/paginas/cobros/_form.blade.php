@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="cliente_id">Cliente</label>
        <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
            <option value="">Selecciona...</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((int) old('cliente_id', $cobro->cliente_id) === $cliente->id)>
                    {{ $cliente->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="factura_id">Factura</label>
        <select class="form-select @error('factura_id') is-invalid @enderror" id="factura_id" name="factura_id">
            <option value="">Cobro a cuenta (sin factura)</option>
            @foreach ($facturas as $factura)
                <option value="{{ $factura->id }}" @selected((int) old('factura_id', $cobro->factura_id) === $factura->id)>
                    {{ $factura->numero_factura }} - {{ $factura->cliente?->nombre }} (saldo ${{ number_format($factura->saldo, 2) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Un cobro a cuenta se puede asignar a una factura despues.</small>
        @error('factura_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $cobro->fecha?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="monto">Monto</label>
        <input class="form-control @error('monto') is-invalid @enderror" type="number" step="0.01" min="0.01"
               id="monto" name="monto" required value="{{ old('monto', $cobro->monto ? (float) $cobro->monto : '') }}">
        @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="forma_pago">Forma de pago</label>
        <select class="form-select @error('forma_pago') is-invalid @enderror" id="forma_pago" name="forma_pago" required>
            @foreach ($formasPago as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('forma_pago', $cobro->forma_pago?->value ?? 'efectivo') === $valor)>
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
                <option value="{{ $cuenta->id }}" @selected((int) old('cuenta_bancaria_id', $cobro->cuenta_bancaria_id) === $cuenta->id)>
                    {{ $cuenta->nombre }} ({{ $cuenta->banco }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-5">
        <label class="form-label" for="referencia">Referencia</label>
        <input class="form-control" type="text" id="referencia" name="referencia" maxlength="120"
               value="{{ old('referencia', $cobro->referencia) }}">
        <small class="text-muted">Numero de transferencia, autorizacion de tarjeta, folio de cheque...</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('ventas.cobros.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
