@extends('layouts.app')

@section('title', 'Listas de precios')
@section('header', 'Listas de precios')
@section('subtitle', 'Mayoreo, menudeo y convenios')

@section('content')
    <x-page-header title="Listas de precios" subtitle="Ventas / Listas de precios">
        <a class="btn btn-primary" href="{{ route('ventas.listas-precios.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva lista
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Codigo o nombre..." />

    <x-card>
        <p class="text-muted small">
            El precio de una venta sale, en este orden: de la lista del cliente, de la lista predeterminada
            y, si no esta en ninguna, del precio del catalogo.
        </p>

        <x-table :head="['Codigo', 'Nombre', 'Moneda', ['label' => 'Productos', 'align' => 'end'], ['label' => 'Clientes', 'align' => 'end'], 'Predeterminada', '']">
            @forelse ($listas as $lista)
                <tr>
                    <td class="text-muted">{{ $lista->codigo }}</td>
                    <td class="fw-bold">{{ $lista->nombre }}</td>
                    <td>{{ $lista->moneda }}</td>
                    <td class="text-end">{{ $lista->items_count }}</td>
                    <td class="text-end">{{ $lista->clientes_count }}</td>
                    <td>{!! $lista->es_predeterminada ? '<i class="bi bi-star-fill text-warning"></i>' : '' !!}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('ventas.listas-precios.show', $lista) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('ventas.listas-precios.edit', $lista) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('ventas.listas-precios.destroy', $lista) }}"
                              data-confirm="Eliminar la lista {{ $lista->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="7" message="No hay listas de precios." icon="tags" />
            @endforelse
        </x-table>

        {{ $listas->links() }}
    </x-card>
@endsection
