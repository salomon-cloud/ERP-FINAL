@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('header', 'Cotizaciones')
@section('subtitle', 'La oferta con vigencia que arranca la venta')

@section('content')
    <x-page-header title="Cotizaciones" subtitle="Ventas / Cotizaciones">
        <a class="btn btn-primary" href="{{ route('ventas.cotizaciones.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva cotizacion
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Folio o cliente..." :dates="true">
        <div class="col-md-2">
            <label class="form-label" for="filtro-cliente">Cliente</label>
            <select class="form-select" id="filtro-cliente" name="cliente_id">
                <option value="">Todos</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((int) request('cliente_id') === $cliente->id)>{{ $cliente->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-select" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <x-card>
        <x-table :head="['Folio', 'Cliente', 'Fecha', 'Vigencia', ['label' => 'Lineas', 'align' => 'end'], ['label' => 'Total', 'align' => 'end'], 'Estado', '']">
            @forelse ($cotizaciones as $cotizacion)
                <tr>
                    <td class="fw-bold">{{ $cotizacion->numero_cotizacion }}</td>
                    <td>{{ $cotizacion->cliente?->nombre ?? '--' }}</td>
                    <td class="small">{{ $cotizacion->fecha?->format('d/m/Y') }}</td>
                    <td class="small {{ $cotizacion->esta_vencida ? 'text-danger fw-bold' : '' }}">
                        {{ $cotizacion->vigencia?->format('d/m/Y') ?? '--' }}
                    </td>
                    <td class="text-end">{{ $cotizacion->lineas_count }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $cotizacion->total, 2) }}</td>
                    <td>
                        @if ($cotizacion->esta_vencida)
                            <x-badge estado="retardo" label="Vencida" />
                        @else
                            <x-badge :estado="$cotizacion->estado->color()" :label="$cotizacion->estado->label()" />
                        @endif
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.cotizaciones.show', $cotizacion) }}"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="8" message="No hay cotizaciones registradas." icon="file-earmark-text" />
            @endforelse
        </x-table>

        {{ $cotizaciones->links() }}
    </x-card>
@endsection
