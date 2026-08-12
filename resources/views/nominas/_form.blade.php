@csrf
<div data-payroll-calc class="row g-3">
    <div class="col-md-6"><label class="form-label">Empleado</label><select name="empleado_id" class="form-select" data-empleado-select required><option value="" data-sueldo="0">Seleccionar</option>@foreach($empleados as $empleado)<option value="{{ $empleado->id }}" data-sueldo="{{ $empleado->sueldo_base }}" @selected(old('empleado_id',$nomina->empleado_id)==$empleado->id)>{{ $empleado->nombre_completo }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Periodo de pago</label><input name="periodo_pago" class="form-control" value="{{ old('periodo_pago',$nomina->periodo_pago) }}" required></div>
    <div class="col-md-3"><label class="form-label">Fecha de pago</label><input type="date" name="fecha_pago" class="form-control" value="{{ old('fecha_pago', optional($nomina->fecha_pago)->format('Y-m-d')) }}" required></div>
    @foreach(['sueldo_base'=>'Sueldo base','bonos'=>'Bonos','horas_extra'=>'Horas extra','deducciones'=>'Deducciones','isr'=>'ISR','imss'=>'IMSS'] as $field=>$label)
        <div class="col-md-4"><label class="form-label">{{ $label }}</label><input type="number" step="0.01" min="0" name="{{ $field }}" class="form-control" value="{{ old($field,$nomina->$field ?? 0) }}" required></div>
    @endforeach
    <div class="col-md-6"><label class="form-label">Total calculado</label><input name="total_preview" class="form-control fw-bold" readonly><div class="form-text">ISR e IMSS se sugieren automaticamente segun el sueldo base; puedes ajustarlos.</div></div>
    <div class="col-md-3"><label class="form-label">Metodo de pago</label><select name="metodo_pago" class="form-select"><option value="">Por definir</option>@foreach(['transferencia'=>'Transferencia','efectivo'=>'Efectivo','cheque'=>'Cheque'] as $value=>$label)<option value="{{ $value }}" @selected(old('metodo_pago',$nomina->metodo_pago)===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Estado</label><select name="estado" class="form-select">@foreach(['pendiente','pagada','cancelada'] as $estado)<option value="{{ $estado }}" @selected(old('estado',$nomina->estado ?: 'pendiente')===$estado)>{{ ucfirst($estado) }}</option>@endforeach</select></div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('nominas.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></div>
