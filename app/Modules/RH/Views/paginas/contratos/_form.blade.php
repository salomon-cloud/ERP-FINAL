@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $contrato->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="numero_contrato">Numero de contrato</label>
        <input class="form-control @error('numero_contrato') is-invalid @enderror" type="text" id="numero_contrato"
               name="numero_contrato" maxlength="30" value="{{ old('numero_contrato', $contrato->numero_contrato) }}">
        @error('numero_contrato') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="tipo_contrato">Tipo de contrato</label>
        <select class="form-select @error('tipo_contrato') is-invalid @enderror" id="tipo_contrato"
                name="tipo_contrato" required>
            @foreach ($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo_contrato', $contrato->tipo_contrato?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tipo_contrato') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_inicio">Inicio</label>
        <input class="form-control @error('fecha_inicio') is-invalid @enderror" type="date" id="fecha_inicio"
               name="fecha_inicio" required value="{{ old('fecha_inicio', $contrato->fecha_inicio?->format('Y-m-d')) }}">
        @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_fin">Fin</label>
        <input class="form-control @error('fecha_fin') is-invalid @enderror" type="date" id="fecha_fin"
               name="fecha_fin" value="{{ old('fecha_fin', $contrato->fecha_fin?->format('Y-m-d')) }}">
        <small class="text-muted">Vacio = indefinido.</small>
        @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="sueldo">Sueldo</label>
        <input class="form-control @error('sueldo') is-invalid @enderror" type="number" step="0.01" min="0"
               id="sueldo" name="sueldo" required value="{{ old('sueldo', (float) $contrato->sueldo) }}">
        @error('sueldo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="jornada_horas">Jornada (horas)</label>
        <input class="form-control @error('jornada_horas') is-invalid @enderror" type="number" step="0.5" min="0.5"
               id="jornada_horas" name="jornada_horas" required value="{{ old('jornada_horas', (float) $contrato->jornada_horas) }}">
        @error('jornada_horas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $contrato->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="firmado_en">Fecha de firma</label>
        <input class="form-control @error('firmado_en') is-invalid @enderror" type="date" id="firmado_en"
               name="firmado_en" value="{{ old('firmado_en', $contrato->firmado_en?->format('Y-m-d')) }}">
        @error('firmado_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="resumen_clausulas">Resumen de clausulas</label>
        <textarea class="form-control @error('resumen_clausulas') is-invalid @enderror" id="resumen_clausulas"
                  name="resumen_clausulas" rows="3">{{ old('resumen_clausulas', $contrato->resumen_clausulas) }}</textarea>
        @error('resumen_clausulas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.contratos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
