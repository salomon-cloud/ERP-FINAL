@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $asistencia->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
               required value="{{ old('fecha', $asistencia->fecha?->format('Y-m-d')) }}">
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $asistencia->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="hora_entrada">Hora de entrada</label>
        <input class="form-control @error('hora_entrada') is-invalid @enderror" type="time" id="hora_entrada"
               name="hora_entrada" value="{{ old('hora_entrada', $asistencia->hora_entrada) }}">
        @error('hora_entrada') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="hora_salida">Hora de salida</label>
        <input class="form-control @error('hora_salida') is-invalid @enderror" type="time" id="hora_salida"
               name="hora_salida" value="{{ old('hora_salida', $asistencia->hora_salida) }}">
        @error('hora_salida') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Horas trabajadas</label>
        <input class="form-control" type="text" value="{{ number_format((float) $asistencia->horas_trabajadas, 2) }}" disabled>
        <small class="text-muted">Se calcula solo a partir de la entrada y la salida.</small>
    </div>

    <div class="col-md-12">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control @error('notas') is-invalid @enderror" id="notas" name="notas" rows="2"
                  maxlength="500">{{ old('notas', $asistencia->notas) }}</textarea>
        @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.asistencias.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
