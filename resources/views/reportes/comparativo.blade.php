@extends('layouts.app')
@section('header','Comparativo financiero por mes')
@section('content')
<div class="soft-card p-3">
    <form class="row g-2 mb-3 no-print">
        <div class="col-md-6"><select name="anio" class="form-select">@foreach($anios as $a)<option value="{{ $a }}" @selected($a==$anio)>{{ $a }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-filter"></i></button></div>
        <div class="col-md-2"><button name="exportar" value="1" class="btn btn-outline-success w-100" title="Exportar CSV"><i class="bi bi-file-earmark-arrow-down"></i></button></div>
        <div class="col-md-2"><button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100"><i class="bi bi-printer"></i></button></div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Mes</th><th>Nominas</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th><th class="text-end">Total del mes</th><th class="text-end">Acumulado</th><th class="text-end">Variacion</th></tr></thead>
            <tbody>@forelse($items as $n)
                <tr><td>{{ $n->etiqueta }}</td><td>{{ $n->total_nominas }}</td><td class="text-end text-success">${{ number_format($n->pagado,2) }}</td><td class="text-end text-warning">${{ number_format($n->pendiente,2) }}</td><td class="text-end fw-bold">${{ number_format($n->total_pagar,2) }}</td><td class="text-end">${{ number_format($n->acumulado,2) }}</td><td class="text-end">@if($n->variacion !== null)<span class="badge-soft badge-{{ $n->variacion < 0 ? 'cancelada' : 'pagada' }}">{{ ($n->variacion >= 0 ? '+' : '').number_format($n->variacion,1) }}%</span>@else<span class="text-muted">-</span>@endif</td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted">Sin nominas en {{ $anio }}.</td></tr>@endforelse
            </tbody>
            @if($items->count() > 0)
            <tfoot>@php $g = $items->sum('total_pagar'); @endphp
            <tr class="table-primary"><th colspan="4">Total anual {{ $anio }}</th><th class="text-end">${{ number_format($g,2) }}</th><th colspan="2"></th></tr>
            </tfoot>@endif
        </table>
    </div>
</div>
@endsection
