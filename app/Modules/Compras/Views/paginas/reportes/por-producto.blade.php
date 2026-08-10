@extends('layouts.app')

@section('title', 'Compras por producto')
@section('header', 'Compras por producto')

@section('content')
    <x-page-header title="Compras por producto" subtitle="Compras / Reportes / Por producto">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'compras.reportes.por-producto'])
    </x-page-header>

    <x-filter-bar :dates="true" placeholder="No aplica" />

    <x-card>
        <p class="text-muted small">
            El costo promedio sale de dividir el importe facturado entre las piezas: es lo que de verdad se
            pago, no lo que dice el catalogo.
        </p>

        <x-table :head="['SKU', 'Producto', ['label' => 'Piezas', 'align' => 'end'], ['label' => 'Importe', 'align' => 'end'], ['label' => 'Costo promedio', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="text-muted">{{ $fila->sku }}</td>
                    <td>{{ $fila->nombre }}</td>
                    <td class="text-end">{{ (float) $fila->piezas }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $fila->importe, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $fila->costo_promedio, 2) }}</td>
                </tr>
            @empty
                <x-empty :colspan="5" message="No hay compras en ese periodo." icon="box-seam" />
            @endforelse
        </x-table>
    </x-card>
@endsection
