{{--
    El carrito: el formulario que agrega una linea a una cotizacion o a un pedido.

    Los dos documentos capturan exactamente lo mismo, asi que comparten esta
    parcial en vez de tener dos formularios que se desincronicen.

    EL PRECIO SE DEJA VACIO A PROPOSITO. Al guardarse, ServicioResolverPrecio lo
    saca de la lista del cliente segun la cantidad; teclearlo es la excepcion
    (un trato puntual), y por encima del descuento maximo pide autorizacion.

    Variables:
      $accion     URL del formulario
      $productos  coleccion de productos seleccionables
      $impuestos  filas de la tabla `impuestos`
      $almacenes  coleccion, o null si el documento no maneja almacen (cotizaciones)
--}}
<form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST" action="{{ $accion }}">
    @csrf

    <div class="col-md-{{ $almacenes ? 3 : 4 }}">
        <label class="form-label" for="producto_id">Producto</label>
        <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
            <option value="">Selecciona...</option>
            @foreach ($productos as $producto)
                <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
            @endforeach
        </select>
        @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($almacenes)
        <div class="col-md-2">
            <label class="form-label" for="almacen_id">Almacen</label>
            <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id">
                <option value="">Sin definir</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}">{{ $almacen->codigo }}</option>
                @endforeach
            </select>
            @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-1">
        <label class="form-label" for="cantidad">Cant.</label>
        <input class="form-control @error('cantidad') is-invalid @enderror" type="number"
               step="0.000001" min="0.000001" id="cantidad" name="cantidad" required>
        @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="precio_unitario">Precio</label>
        <input class="form-control @error('precio_unitario') is-invalid @enderror" type="number"
               step="0.01" min="0" id="precio_unitario" name="precio_unitario" placeholder="De la lista">
        @error('precio_unitario') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-1">
        <label class="form-label" for="porcentaje_descuento">Desc. %</label>
        <input class="form-control @error('porcentaje_descuento') is-invalid @enderror" type="number"
               step="0.01" min="0" max="100" id="porcentaje_descuento" name="porcentaje_descuento" value="0">
        @error('porcentaje_descuento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="impuesto_id">Impuesto</label>
        <select class="form-select" id="impuesto_id" name="impuesto_id">
            <option value="">El del producto</option>
            @foreach ($impuestos as $impuesto)
                <option value="{{ $impuesto->id }}">{{ $impuesto->codigo }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-1">
        <button class="btn btn-outline-primary w-100" type="submit" title="Agregar linea">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>

    <div class="col-12">
        <small class="text-muted">
            Deja el precio vacio para que lo resuelva la lista del cliente. Un descuento por encima del
            {{ rtrim(rtrim(number_format((float) config('sisen.sales.max_discount', 100), 2, '.', ''), '0'), '.') }}%
            necesita autorizacion.
        </small>
    </div>
</form>
