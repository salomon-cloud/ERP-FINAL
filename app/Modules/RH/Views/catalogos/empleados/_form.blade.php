@csrf

<h6 class="fw-bold text-uppercase text-muted mb-3">Datos personales</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="numero_empleado">Numero de empleado</label>
        <input class="form-control @error('numero_empleado') is-invalid @enderror" type="text" id="numero_empleado"
               name="numero_empleado" maxlength="30" value="{{ old('numero_empleado', $empleado->numero_empleado) }}">
        @error('numero_empleado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control @error('nombre') is-invalid @enderror" type="text" id="nombre" name="nombre"
               required value="{{ old('nombre', $empleado->nombre) }}">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="apellidos">Apellidos</label>
        <input class="form-control @error('apellidos') is-invalid @enderror" type="text" id="apellidos"
               name="apellidos" required value="{{ old('apellidos', $empleado->apellidos) }}">
        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="genero">Genero</label>
        <select class="form-select @error('genero') is-invalid @enderror" id="genero" name="genero">
            <option value="">Sin especificar</option>
            @foreach ($generos as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('genero', $empleado->genero?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('genero') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
        <input class="form-control @error('fecha_nacimiento') is-invalid @enderror" type="date" id="fecha_nacimiento"
               name="fecha_nacimiento" required
               value="{{ old('fecha_nacimiento', $empleado->fecha_nacimiento?->format('Y-m-d')) }}">
        @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="curp">CURP</label>
        <input class="form-control @error('curp') is-invalid @enderror" type="text" id="curp" name="curp"
               required maxlength="18" value="{{ old('curp', $empleado->curp) }}">
        @error('curp') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="rfc">RFC</label>
        <input class="form-control @error('rfc') is-invalid @enderror" type="text" id="rfc" name="rfc"
               required maxlength="13" value="{{ old('rfc', $empleado->rfc) }}">
        @error('rfc') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<h6 class="fw-bold text-uppercase text-muted mt-4 mb-3">Contacto</h6>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="correo">Correo</label>
        <input class="form-control @error('correo') is-invalid @enderror" type="email" id="correo" name="correo"
               required value="{{ old('correo', $empleado->correo) }}">
        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="telefono">Telefono</label>
        <input class="form-control @error('telefono') is-invalid @enderror" type="text" id="telefono" name="telefono"
               maxlength="30" value="{{ old('telefono', $empleado->telefono) }}">
        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="direccion">Direccion</label>
        <input class="form-control @error('direccion') is-invalid @enderror" type="text" id="direccion"
               name="direccion" value="{{ old('direccion', $empleado->direccion) }}">
        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<h6 class="fw-bold text-uppercase text-muted mt-4 mb-3">Puesto y contratacion</h6>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="departamento_id">Departamento</label>
        <select class="form-select @error('departamento_id') is-invalid @enderror" id="departamento_id"
                name="departamento_id" required>
            <option value="">Selecciona...</option>
            @foreach ($departamentos as $departamento)
                <option value="{{ $departamento->id }}" @selected((int) old('departamento_id', $empleado->departamento_id) === $departamento->id)>
                    {{ $departamento->nombre }}
                </option>
            @endforeach
        </select>
        @error('departamento_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="puesto_id">Puesto</label>
        <select class="form-select @error('puesto_id') is-invalid @enderror" id="puesto_id" name="puesto_id" required>
            <option value="">Selecciona...</option>
            @foreach ($puestos as $puesto)
                <option value="{{ $puesto->id }}" @selected((int) old('puesto_id', $empleado->puesto_id) === $puesto->id)>
                    {{ $puesto->nombre }} @if ($puesto->departamento) ({{ $puesto->departamento->nombre }}) @endif
                </option>
            @endforeach
        </select>
        @error('puesto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="jefe_id">Jefe directo</label>
        <select class="form-select @error('jefe_id') is-invalid @enderror" id="jefe_id" name="jefe_id">
            <option value="">Sin jefe asignado</option>
            @foreach ($jefes as $jefe)
                <option value="{{ $jefe->id }}" @selected((int) old('jefe_id', $empleado->jefe_id) === $jefe->id)>
                    {{ $jefe->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('jefe_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_contratacion">Fecha de contratacion</label>
        <input class="form-control @error('fecha_contratacion') is-invalid @enderror" type="date"
               id="fecha_contratacion" name="fecha_contratacion" required
               value="{{ old('fecha_contratacion', $empleado->fecha_contratacion?->format('Y-m-d')) }}">
        @error('fecha_contratacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="tipo_contrato">Tipo de contrato</label>
        <select class="form-select @error('tipo_contrato') is-invalid @enderror" id="tipo_contrato"
                name="tipo_contrato" required>
            @foreach ($tiposContrato as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo_contrato', $empleado->tipo_contrato?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tipo_contrato') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="fecha_baja">Fecha de baja</label>
        <input class="form-control @error('fecha_baja') is-invalid @enderror" type="date" id="fecha_baja"
               name="fecha_baja" value="{{ old('fecha_baja', $empleado->fecha_baja?->format('Y-m-d')) }}">
        @error('fecha_baja') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $empleado->estado?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="motivo_baja">Motivo de baja</label>
        <input class="form-control @error('motivo_baja') is-invalid @enderror" type="text" id="motivo_baja"
               name="motivo_baja" maxlength="300" value="{{ old('motivo_baja', $empleado->motivo_baja) }}">
        @error('motivo_baja') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<h6 class="fw-bold text-uppercase text-muted mt-4 mb-3">Nomina y datos bancarios</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="sueldo_base">Sueldo base mensual</label>
        <input class="form-control @error('sueldo_base') is-invalid @enderror" type="number" step="0.01" min="0"
               id="sueldo_base" name="sueldo_base" required value="{{ old('sueldo_base', (float) $empleado->sueldo_base) }}">
        @error('sueldo_base') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label" for="moneda">Moneda</label>
        <input class="form-control @error('moneda') is-invalid @enderror" type="text" id="moneda" name="moneda"
               required maxlength="3" value="{{ old('moneda', $empleado->moneda ?? 'MXN') }}">
        @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="frecuencia_pago">Frecuencia de pago</label>
        <select class="form-select @error('frecuencia_pago') is-invalid @enderror" id="frecuencia_pago"
                name="frecuencia_pago" required>
            @foreach ($frecuencias as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('frecuencia_pago', $empleado->frecuencia_pago?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('frecuencia_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="nss">NSS (IMSS)</label>
        <input class="form-control @error('nss') is-invalid @enderror" type="text" id="nss" name="nss"
               maxlength="30" value="{{ old('nss', $empleado->nss) }}">
        @error('nss') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="banco">Banco</label>
        <input class="form-control @error('banco') is-invalid @enderror" type="text" id="banco" name="banco"
               maxlength="150" value="{{ old('banco', $empleado->banco) }}">
        @error('banco') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="cuenta_bancaria">Cuenta bancaria</label>
        <input class="form-control @error('cuenta_bancaria') is-invalid @enderror" type="text" id="cuenta_bancaria"
               name="cuenta_bancaria" maxlength="60" value="{{ old('cuenta_bancaria', $empleado->cuenta_bancaria) }}">
        @error('cuenta_bancaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="fotografia">Fotografia</label>
        <input class="form-control @error('fotografia') is-invalid @enderror" type="file" id="fotografia"
               name="fotografia" accept="image/*">
        <small class="text-muted">Deja vacio para conservar la actual.</small>
        @error('fotografia') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-outline-secondary" href="{{ route('rh.empleados.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
