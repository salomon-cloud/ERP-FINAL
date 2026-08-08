@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $evaluacion->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="evaluador_id">Evaluador</label>
        <select class="form-select @error('evaluador_id') is-invalid @enderror" id="evaluador_id" name="evaluador_id">
            <option value="">Sin asignar</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('evaluador_id', $evaluacion->evaluador_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('evaluador_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="periodo_evaluado">Periodo</label>
        <input class="form-control @error('periodo_evaluado') is-invalid @enderror" type="text" id="periodo_evaluado"
               name="periodo_evaluado" required maxlength="50" placeholder="2026-S1"
               value="{{ old('periodo_evaluado', $evaluacion->periodo_evaluado) }}">
        @error('periodo_evaluado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="calificacion">Calificacion</label>
        <input class="form-control @error('calificacion') is-invalid @enderror" type="number" step="0.01" min="0"
               max="100" id="calificacion" name="calificacion"
               value="{{ old('calificacion', $evaluacion->calificacion) }}">
        @error('calificacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="fortalezas">Fortalezas</label>
        <textarea class="form-control @error('fortalezas') is-invalid @enderror" id="fortalezas" name="fortalezas"
                  rows="4">{{ old('fortalezas', $evaluacion->fortalezas) }}</textarea>
        @error('fortalezas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="areas_mejora">Areas de mejora</label>
        <textarea class="form-control @error('areas_mejora') is-invalid @enderror" id="areas_mejora"
                  name="areas_mejora" rows="4">{{ old('areas_mejora', $evaluacion->areas_mejora) }}</textarea>
        @error('areas_mejora') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $evaluacion->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="evaluado_en">Fecha de evaluacion</label>
        <input class="form-control @error('evaluado_en') is-invalid @enderror" type="date" id="evaluado_en"
               name="evaluado_en" value="{{ old('evaluado_en', $evaluacion->evaluado_en?->format('Y-m-d')) }}">
        @error('evaluado_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.evaluaciones.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
