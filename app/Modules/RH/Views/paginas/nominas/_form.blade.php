@csrf

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="empleado_id">Empleado</label>
        <select class="form-select @error('empleado_id') is-invalid @enderror" id="empleado_id" name="empleado_id" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected((int) old('empleado_id', $nomina->empleado_id) === $empleado->id)>
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('empleado_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="periodo_pago">Periodo de pago</label>
        <input class="form-control @error('periodo_pago') is-invalid @enderror" type="text" id="periodo_pago"
               name="periodo_pago" required value="{{ old('periodo_pago', $nomina->periodo_pago) }}">
        @error('periodo_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_pago">Fecha de pago</label>
        <input class="form-control @error('fecha_pago') is-invalid @enderror" type="date" id="fecha_pago"
               name="fecha_pago" required value="{{ old('fecha_pago', $nomina->fecha_pago?->format('Y-m-d')) }}">
        @error('fecha_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<h6 class="fw-bold text-uppercase text-muted mt-4 mb-3">Percepciones</h6>
<div class="row g-3">
    @foreach ([
        'sueldo_base' => 'Sueldo base',
        'bonos' => 'Bonos',
        'horas_extra' => 'Importe de horas extra',
        'horas_extra_cantidad' => 'Cantidad de horas extra',
    ] as $campo => $etiqueta)
        <div class="col-md-3">
            <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
            <input class="form-control @error($campo) is-invalid @enderror" type="number" step="0.01" min="0"
                   id="{{ $campo }}" name="{{ $campo }}" required
                   value="{{ old($campo, (float) $nomina->{$campo}) }}">
            @error($campo) <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endforeach
</div>

<h6 class="fw-bold text-uppercase text-muted mt-4 mb-3">Deducciones</h6>
<div class="row g-3">
    @foreach ([
        'deducciones' => 'Otras deducciones',
        'dias_ausencia' => 'Dias de ausencia',
        'isr' => 'ISR',
        'imss' => 'IMSS',
    ] as $campo => $etiqueta)
        <div class="col-md-3">
            <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
            <input class="form-control @error($campo) is-invalid @enderror" type="number" step="0.01" min="0"
                   id="{{ $campo }}" name="{{ $campo }}" required
                   value="{{ old($campo, (float) $nomina->{$campo}) }}">
            @error($campo) <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-md-3">
        <label class="form-label">Total a pagar</label>
        <input class="form-control fw-bold" type="text" value="${{ number_format((float) $nomina->total_pagar, 2) }}" disabled>
        <small class="text-muted">Se calcula solo al guardar.</small>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $nomina->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="notas">Notas</label>
        <input class="form-control @error('notas') is-invalid @enderror" type="text" id="notas" name="notas"
               maxlength="500" value="{{ old('notas', $nomina->notas) }}">
        @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.nominas.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
