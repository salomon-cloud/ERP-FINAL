@extends('layouts.app')

@section('title', 'Unidades de medida')
@section('header', 'Unidades de medida')
@section('subtitle', 'El factor que convierte a unidad base')

@section('content')
    <x-page-header title="Unidades de medida" subtitle="Inventario / Unidades">
        <a class="btn btn-primary" href="{{ route('inventario.unidades.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva unidad
        </a>
    </x-page-header>

    <x-card>
        <p class="text-muted small">
            El inventario se guarda siempre en unidad base. Si una CAJA tiene factor 12, vender
            una caja saca doce piezas del almacen.
        </p>

        <x-table :head="['Codigo', 'Nombre', ['label' => 'Factor base', 'align' => 'end'], 'Es base', ['label' => 'Productos', 'align' => 'end'], '']">
            @forelse ($unidades as $unidad)
                <tr>
                    <td class="fw-bold">{{ $unidad->codigo }}</td>
                    <td>{{ $unidad->nombre }}</td>
                    <td class="text-end">{{ (float) $unidad->factor_base }}</td>
                    <td>{!! $unidad->es_base ? '<i class="bi bi-check-lg text-success"></i>' : '' !!}</td>
                    <td class="text-end">{{ $unidad->productos_count }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.unidades.edit', $unidad) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('inventario.unidades.destroy', $unidad) }}"
                              data-confirm="Eliminar la unidad {{ $unidad->codigo }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay unidades de medida." icon="rulers" />
            @endforelse
        </x-table>

        {{ $unidades->links() }}
    </x-card>
@endsection
