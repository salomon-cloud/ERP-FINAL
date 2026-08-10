@extends('layouts.app')

@section('title', 'Valoracion por categoria')
@section('header', 'Valoracion del inventario')

@section('content')
    <x-page-header title="Valoracion por categoria" subtitle="Inventario / Reportes / Valoracion">
        @include('compartido::partials.acciones-reporte', ['ruta' => 'inventario.reportes.valoracion'])
    </x-page-header>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">Existencia fisica por costo. Lo apartado sigue contando: no ha salido del almacen.</span>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-bold">Valor total</div>
                <div class="fs-4 fw-bold">${{ number_format($valorTotal, 2) }}</div>
            </div>
        </div>

        <x-table :head="['Categoria', ['label' => 'Productos', 'align' => 'end'], ['label' => 'Piezas', 'align' => 'end'], ['label' => 'Valor', 'align' => 'end'], ['label' => '% del total', 'align' => 'end']]">
            @forelse ($filas as $fila)
                <tr>
                    <td class="fw-bold">{{ $fila->categoria }}</td>
                    <td class="text-end">{{ $fila->productos }}</td>
                    <td class="text-end">{{ $fila->piezas + 0 }}</td>
                    <td class="text-end">${{ number_format((float) $fila->valor, 2) }}</td>
                    <td class="text-end text-muted">
                        {{ $valorTotal > 0 ? number_format((float) $fila->valor / $valorTotal * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @empty
                <x-empty :colspan="5" message="Todavia no hay inventario que valorizar." icon="cash-coin" />
            @endforelse
        </x-table>
    </x-card>
@endsection
