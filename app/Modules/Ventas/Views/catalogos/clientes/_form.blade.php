@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="codigo">Codigo</label>
        <input class="form-control @error('codigo') is-invalid @enderror" type="text" id="codigo" name="codigo"
               maxlength="30" required value="{{ old('codigo', $cliente->codigo) }}">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nombre">Nombre comercial</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="200" required value="{{ old('nombre', $cliente->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $cliente->estado?->value ?? 'activo') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="razon_social">Razon social</label>
        <input class="form-control" type="text" id="razon_social" name="razon_social" maxlength="200"
               value="{{ old('razon_social', $cliente->razon_social) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label" for="rfc">RFC</label>
        <input class="form-control @error('rfc') is-invalid @enderror" type="text" id="rfc" name="rfc"
               maxlength="30" value="{{ old('rfc', $cliente->rfc) }}">
        <small class="text-muted">Opcional: se puede facturar a publico en general.</small>
        @error('rfc') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="telefono">Telefono</label>
        <input class="form-control" type="text" id="telefono" name="telefono" maxlength="30"
               value="{{ old('telefono', $cliente->telefono) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="correo">Correo</label>
        <input class="form-control @error('correo') is-invalid @enderror" type="email" id="correo" name="correo"
               maxlength="150" value="{{ old('correo', $cliente->correo) }}">
        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="condicion_pago_id">Condicion de pago</label>
        <select class="form-select" id="condicion_pago_id" name="condicion_pago_id">
            <option value="">Sin definir</option>
            @foreach ($condicionesPago as $condicion)
                <option value="{{ $condicion->id }}" @selected((int) old('condicion_pago_id', $cliente->condicion_pago_id) === $condicion->id)>
                    {{ $condicion->nombre }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Define la fecha de vencimiento de sus facturas.</small>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="lista_precio_id">Lista de precios</label>
        <select class="form-select" id="lista_precio_id" name="lista_precio_id">
            <option value="">Usar la predeterminada</option>
            @foreach ($listas as $lista)
                <option value="{{ $lista->id }}" @selected((int) old('lista_precio_id', $cliente->lista_precio_id) === $lista->id)>
                    {{ $lista->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label" for="moneda">Moneda</label>
        <select class="form-select @error('moneda') is-invalid @enderror" id="moneda" name="moneda" required>
            @foreach ($monedas as $moneda)
                <option value="{{ $moneda->codigo }}" @selected(old('moneda', $cliente->moneda ?? 'MXN') === $moneda->codigo)>
                    {{ $moneda->codigo }}
                </option>
            @endforeach
        </select>
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="limite_credito">Limite de credito</label>
        <input class="form-control @error('limite_credito') is-invalid @enderror" type="number" step="0.01" min="0"
               id="limite_credito" name="limite_credito" required
               value="{{ old('limite_credito', (float) $cliente->limite_credito) }}">
        @error('limite_credito') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="direccion">Direccion</label>
        <textarea class="form-control" id="direccion" name="direccion" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('ventas.clientes.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
