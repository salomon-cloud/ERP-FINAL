@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="sku">SKU</label>
        <input class="form-control @error('sku') is-invalid @enderror" type="text" id="sku" name="sku"
               maxlength="50" required value="{{ old('sku', $producto->sku) }}">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               maxlength="200" required value="{{ old('nombre', $producto->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $producto->estado?->value ?? 'activo') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="categoria_id">Categoria</label>
        <select class="form-select @error('categoria_id') is-invalid @enderror" id="categoria_id" name="categoria_id">
            <option value="">Sin categoria</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $producto->categoria_id) === $categoria->id)>
                    {{ $categoria->nombre_ruta }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Define en que pestana aparece y que campos importan.</small>
        @error('categoria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="unidad_id">Unidad de medida</label>
        <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id">
            <option value="">Sin unidad</option>
            @foreach ($unidades as $unidad)
                <option value="{{ $unidad->id }}" @selected((int) old('unidad_id', $producto->unidad_id) === $unidad->id)>
                    {{ $unidad->codigo }} - {{ $unidad->nombre }} (x{{ (float) $unidad->factor_base }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">El inventario siempre se guarda en unidad base.</small>
        @error('unidad_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="impuesto_id">Impuesto</label>
        <select class="form-select @error('impuesto_id') is-invalid @enderror" id="impuesto_id" name="impuesto_id">
            <option value="">Sin impuesto</option>
            @foreach ($impuestos as $impuesto)
                <option value="{{ $impuesto->id }}" @selected((int) old('impuesto_id', $producto->impuesto_id) === $impuesto->id)>
                    {{ $impuesto->codigo }} ({{ (float) $impuesto->tasa * 100 }}%)
                </option>
            @endforeach
        </select>
        @error('impuesto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="costo">Costo</label>
        <input class="form-control @error('costo') is-invalid @enderror" type="number" step="0.01" min="0"
               id="costo" name="costo" required value="{{ old('costo', (float) $producto->costo) }}">
        @error('costo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="precio_venta">Precio de venta</label>
        <input class="form-control @error('precio_venta') is-invalid @enderror" type="number" step="0.01" min="0"
               id="precio_venta" name="precio_venta" required value="{{ old('precio_venta', (float) $producto->precio_venta) }}">
        @error('precio_venta') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="stock_minimo">Stock minimo</label>
        <input class="form-control @error('stock_minimo') is-invalid @enderror" type="number" step="0.000001" min="0"
               id="stock_minimo" name="stock_minimo" value="{{ old('stock_minimo', (float) $producto->stock_minimo) }}">
        @error('stock_minimo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="stock_maximo">Stock maximo</label>
        <input class="form-control @error('stock_maximo') is-invalid @enderror" type="number" step="0.000001" min="0"
               id="stock_maximo" name="stock_maximo" value="{{ old('stock_maximo', (float) $producto->stock_maximo) }}">
        <small class="text-muted">El minimo por almacen se afina en "Minimos y maximos".</small>
        @error('stock_maximo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="descripcion">Descripcion</label>
        <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion"
                  rows="2">{{ old('descripcion', $producto->descripcion) }}</textarea>
        @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <div class="d-flex flex-wrap gap-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="es_vendible" name="es_vendible"
                       @checked(old('es_vendible', $producto->exists ? $producto->es_vendible : true))>
                <label class="form-check-label" for="es_vendible">Se puede vender</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="es_comprable" name="es_comprable"
                       @checked(old('es_comprable', $producto->exists ? $producto->es_comprable : true))>
                <label class="form-check-label" for="es_comprable">Se puede comprar</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="es_inventariable" name="es_inventariable"
                       @checked(old('es_inventariable', $producto->exists ? $producto->es_inventariable : true))>
                <label class="form-check-label" for="es_inventariable">Lleva inventario</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="rastrea_serie" name="rastrea_serie"
                       @checked(old('rastrea_serie', $producto->rastrea_serie))>
                <label class="form-check-label" for="rastrea_serie">Rastrea numero de serie</label>
            </div>
        </div>
        <small class="text-muted">Un servicio se vende y se factura, pero no descuenta existencia: desmarca "lleva inventario".</small>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.productos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
