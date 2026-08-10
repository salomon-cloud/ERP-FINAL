@extends('layouts.app')

@section('title', 'Categorias')
@section('header', 'Categorias de producto')
@section('subtitle', 'El arbol que organiza el catalogo unico')

@section('content')
    <x-page-header title="Categorias" subtitle="Inventario / Categorias">
        <a class="btn btn-primary" href="{{ route('inventario.categorias.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nueva categoria
        </a>
    </x-page-header>

    <x-filter-bar placeholder="Codigo o nombre..." />

    <x-card>
        <x-table :head="['Codigo', 'Nombre', 'Categoria padre', ['label' => 'Productos', 'align' => 'end'], 'Estado', '']">
            @forelse ($categorias as $categoria)
                <tr>
                    <td class="text-muted">{{ $categoria->codigo }}</td>
                    <td class="fw-bold">{{ $categoria->nombre }}</td>
                    <td>{{ $categoria->padre?->nombre ?? '--' }}</td>
                    <td class="text-end">
                        <a href="{{ route('inventario.productos.index', ['categoria_id' => $categoria->id]) }}">
                            {{ $categoria->productos_count }}
                        </a>
                    </td>
                    <td><x-badge :estado="$categoria->activo ? 'activo' : 'inactivo'" :label="$categoria->activo ? 'Activa' : 'Inactiva'" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.categorias.edit', $categoria) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('inventario.categorias.destroy', $categoria) }}"
                              data-confirm="Eliminar la categoria {{ $categoria->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="No hay categorias registradas." icon="diagram-2" />
            @endforelse
        </x-table>

        {{ $categorias->links() }}
    </x-card>
@endsection
