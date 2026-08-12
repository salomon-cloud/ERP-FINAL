@extends('layouts.app')
@section('header','Resumen financiero por periodo')
@section('content')
<div class="soft-card p-3">
    <form class="row g-2 mb-3 no-print">
        <div class="col-md-4"><select name="periodo" class="form-select"><option value="">Todos los periodos</option>@foreach($periodos as $periodo)<option value="{{ $periodo['periodo'] }}" @selected(request('periodo')==$periodo['periodo'])>{{ $periodo['etiqueta'] }}</option>@endforeach</select></div>
        <div class="col-md-4"><select name="empleado_id" class="form-select"><option value="">Empleado</option>@foreach($empleados as $empleado)<option value="{{ $empleado->id }}" @selected(request('empleado_id')==$empleado->id)>{{ $empleado->nombre_completo }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="bi bi-filter"></i></button></div>
        <div class="col-md-1"><button name="exportar" value="1" class="btn btn-outline-success w-100" title="Exportar CSV"><i class="bi bi-file-earmark-arrow-down"></i></button></div>
        <div class="col-md-2"><button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100"><i class="bi bi-printer"></i></button></div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Periodo</th><th>Nominas</th><th>Sueldo</th><th>Bonos + H. extra</th><th>Deducciones + ISR + IMSS</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th></tr></thead>
            <tbody>@forelse($items as $n)
                <tr><td>{{ $n->etiqueta }}</td><td>{{ $n->total_nominas }}</td><td>${{ number_format($n->sueldo_base,2) }}</td><td>${{ number_format($n->bonos + $n->horas_extra,2) }}</td><td>${{ number_format($n->deducciones + $n->isr + $n->imss,2) }}</td><td class="text-end fw-bold">${{ number_format($n->total_pagar,2) }}</td><td class="text-end text-success">${{ number_format($n->pagado,2) }}</td><td class="text-end text-warning">${{ number_format($n->pendiente,2) }}</td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted">Sin datos para el filtro.</td></tr>@endforelse
            </tbody>
            @if($items->count() > 0)
            <tfoot>@php $g = $items->values(); $s = ['total_nominas'=>0,'sueldo_base'=>0,'bonos'=>0,'horas_extra'=>0,'deducciones'=>0,'isr'=>0,'imss'=>0,'total_pagar'=>0,'pagado'=>0,'pendiente'=>0]; foreach($g as $r){ foreach($s as $k=>$v){ $s[$k]+= (float)$r->$k; } } @endphp
            <tr class="table-primary"><th>Totales</th><th>{{ $s['total_nominas'] }}</th><th>${{ number_format($s['sueldo_base'],2) }}</th><th>${{ number_format($s['bonos'] + $s['horas_extra'],2) }}</th><th>${{ number_format($s['deducciones'] + $s['isr'] + $s['imss'],2) }}</th><th class="text-end">${{ number_format($s['total_pagar'],2) }}</th><th class="text-end text-success">${{ number_format($s['pagado'],2) }}</th><th class="text-end text-warning">${{ number_format($s['pendiente'],2) }}</th></tr>
            </tfoot>@endif
        </table>
    </div>
</div>
@endsection
