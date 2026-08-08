@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="codigo_periodo">Codigo del periodo</label>
        <input class="form-control @error('codigo_periodo') is-invalid @enderror" type="text" id="codigo_periodo"
               name="codigo_periodo" required maxlength="30" placeholder="2026-Q15"
               value="{{ old('codigo_periodo', $periodo->codigo_periodo) }}">
        @error('codigo_periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="frecuencia">Frecuencia</label>
        <select class="form-select @error('frecuencia') is-invalid @enderror" id="frecuencia" name="frecuencia" required>
            @foreach ($frecuencias as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('frecuencia', $periodo->frecuencia?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <small class="text-muted">Solo entran empleados con esta misma frecuencia.</small>
        @error('frecuencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $periodo->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="fecha_inicio">Inicio del periodo</label>
        <input class="form-control @error('fecha_inicio') is-invalid @enderror" type="date" id="fecha_inicio"
               name="fecha_inicio" required value="{{ old('fecha_inicio', $periodo->fecha_inicio?->format('Y-m-d')) }}">
        @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="fecha_fin">Fin del periodo</label>
        <input class="form-control @error('fecha_fin') is-invalid @enderror" type="date" id="fecha_fin"
               name="fecha_fin" required value="{{ old('fecha_fin', $periodo->fecha_fin?->format('Y-m-d')) }}">
        @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="fecha_pago">Fecha de pago</label>
        <input class="form-control @error('fecha_pago') is-invalid @enderror" type="date" id="fecha_pago"
               name="fecha_pago" required value="{{ old('fecha_pago', $periodo->fecha_pago?->format('Y-m-d')) }}">
        @error('fecha_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.nomina-periodos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
