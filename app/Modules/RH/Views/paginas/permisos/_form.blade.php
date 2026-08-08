@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $permiso->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="tipo">Tipo</label>
        <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
            @foreach ($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo', $permiso->tipo?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="con_goce">Goce de sueldo</label>
        <select class="form-select @error('con_goce') is-invalid @enderror" id="con_goce" name="con_goce">
            <option value="1" @selected((bool) old('con_goce', $permiso->con_goce))>Con goce</option>
            <option value="0" @selected(! (bool) old('con_goce', $permiso->con_goce))>Sin goce (se descuenta)</option>
        </select>
        @error('con_goce') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_inicio">Fecha de inicio</label>
        <input class="form-control @error('fecha_inicio') is-invalid @enderror" type="date" id="fecha_inicio"
               name="fecha_inicio" required value="{{ old('fecha_inicio', $permiso->fecha_inicio?->format('Y-m-d')) }}">
        @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_fin">Fecha de fin</label>
        <input class="form-control @error('fecha_fin') is-invalid @enderror" type="date" id="fecha_fin"
               name="fecha_fin" required value="{{ old('fecha_fin', $permiso->fecha_fin?->format('Y-m-d')) }}">
        @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="dias">Dias</label>
        <input class="form-control @error('dias') is-invalid @enderror" type="number" step="0.5" min="0" id="dias"
               name="dias" value="{{ old('dias', (float) $permiso->dias ?: null) }}">
        <small class="text-muted">Vacio = se calcula del rango.</small>
        @error('dias') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="motivo">Motivo</label>
        <textarea class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" rows="3"
                  required>{{ old('motivo', $permiso->motivo) }}</textarea>
        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.permisos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
