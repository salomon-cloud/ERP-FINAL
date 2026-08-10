@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="almacen_origen_id">Almacen de origen</label>
        <select class="form-select @error('almacen_origen_id') is-invalid @enderror" id="almacen_origen_id"
                name="almacen_origen_id" required>
            <option value="">Selecciona...</option>
            @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->id }}" @selected((int) old('almacen_origen_id', $traspaso->almacen_origen_id) === $almacen->id)>
                    {{ $almacen->codigo }} - {{ $almacen->nombre }}
                </option>
            @endforeach
        </select>
        @error('almacen_origen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="almacen_destino_id">Almacen de destino</label>
        <select class="form-select @error('almacen_destino_id') is-invalid @enderror" id="almacen_destino_id"
                name="almacen_destino_id" required>
            <option value="">Selecciona...</option>
            @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->id }}" @selected((int) old('almacen_destino_id', $traspaso->almacen_destino_id) === $almacen->id)>
                    {{ $almacen->codigo }} - {{ $almacen->nombre }}
                </option>
            @endforeach
        </select>
        @error('almacen_destino_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('inventario.traspasos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
