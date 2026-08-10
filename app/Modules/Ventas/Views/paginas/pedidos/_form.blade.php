@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="cliente_id">Cliente</label>
        <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
            <option value="">Selecciona...</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((int) old('cliente_id', $pedido->cliente_id) === $cliente->id)>
                    {{ $cliente->etiqueta }}
                </option>
            @endforeach
        </select>
        @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="lista_precio_id">Lista de precios</label>
        <select class="form-select" id="lista_precio_id" name="lista_precio_id">
            <option value="">La del cliente</option>
            @foreach ($listas as $lista)
                <option value="{{ $lista->id }}" @selected((int) old('lista_precio_id', $pedido->lista_precio_id) === $lista->id)>
                    {{ $lista->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $pedido->fecha?->toDateString() ?? now()->toDateString()) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="fecha_entrega">Entrega</label>
        <input class="form-control @error('fecha_entrega') is-invalid @enderror" type="date"
               id="fecha_entrega" name="fecha_entrega"
               value="{{ old('fecha_entrega', $pedido->fecha_entrega?->toDateString()) }}">
        @error('fecha_entrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="moneda">Moneda</label>
        <input class="form-control @error('moneda') is-invalid @enderror" type="text" id="moneda" name="moneda"
               maxlength="3" required value="{{ old('moneda', $pedido->moneda ?? 'MXN') }}">
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-10">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control" id="notas" name="notas" rows="2">{{ old('notas', $pedido->notas) }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('ventas.pedidos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
