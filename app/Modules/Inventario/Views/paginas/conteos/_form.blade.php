@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="almacen_id">Almacen</label>
        <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id" required>
            <option value="">Selecciona...</option>
            @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->id }}" @selected((int) old('almacen_id', $conteo->almacen_id) === $almacen->id)>
                    {{ $almacen->codigo }} - {{ $almacen->nombre }}
                </option>
            @endforeach
        </select>
        @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="ubicacion_id">Ubicacion</label>
        <select class="form-select @error('ubicacion_id') is-invalid @enderror" id="ubicacion_id" name="ubicacion_id">
            <option value="">Todo el almacen</option>
            @foreach ($almacenes as $almacen)
                <optgroup label="{{ $almacen->codigo }}">
                    @foreach ($almacen->ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected((int) old('ubicacion_id', $conteo->ubicacion_id) === $ubicacion->id)>
                            {{ $ubicacion->codigo }} - {{ $ubicacion->nombre }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <small class="text-muted">Deja vacio para un conteo completo del almacen.</small>
        @error('ubicacion_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.conteos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
