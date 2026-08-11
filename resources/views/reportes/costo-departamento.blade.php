@extends('layouts.app')
@section('header','Costo de nomina por departamento')
@section('content')
<div class="soft-card p-3">
    <form class="row g-2 mb-3 no-print">
        <div class="col-md-6"><select name="periodo" class="form-select"><option value="">Todos los periodos</option>@foreach($periodos as $periodo)<option value="{{ $periodo['periodo'] }}" @selected(request('periodo')==$periodo['periodo'])>{{ $periodo['etiqueta'] }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-filter"></i></button></div>
        <div class="col-md-2"><button name="exportar" value="1" class="btn btn-outline-success w-100" title="Exportar CSV"><i class="bi bi-file-earmark-arrow-down"></i></button></div>
        <div class="col-md-2"><button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100"><i class="bi bi-printer"></i></button></div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Departamento</th><th>Responsable</th><th>Empleados</th><th>Sueldo base</th><th class="text-end">Total nomina</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th></tr></thead>
            <tbody>@forelse($items as $d)
                <tr><td>{{ $d->nombre }}</td><td>{{ $d->responsable }}</td><td>{{ $d->empleados }}</td><td>${{ number_format($d->sueldo_base,2) }}</td><td class="text-end fw-bold">${{ number_format($d->total_pagar,2) }}</td><td class="text-end text-success">${{ number_format($d->pagado,2) }}</td><td class="text-end text-warning">${{ number_format($d->pendiente,2) }}</td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted">Sin datos para el filtro.</td></tr>@endforelse
            </tbody>
            @if($gran_total && $gran_total->total_pagar > 0)
            <tfoot><tr class="table-primary"><th colspan="4">Gran total</th><th class="text-end">${{ number_format($gran_total->total_pagar,2) }}</th><th colspan="2"></th></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
