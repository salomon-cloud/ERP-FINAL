@extends('layouts.app')

@section('title', 'Productos')
@section('header', 'Productos')
@section('subtitle', 'Catalogo unico: medicamentos, insumos, papeleria y lo demas')

@section('content')
    <x-page-header title="Productos" subtitle="Inventario / Productos">
        <a class="btn btn-primary" href="{{ route('inventario.productos.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo producto
        </a>
    </x-page-header>

    {{-- Un solo catalogo con pestanas por categoria, no una pantalla por
         familia de articulos: la logica es la misma y duplicarla seria
         duplicar tambien los errores (docs/david.md D4). --}}
    <ul class="nav nav-pills mb-3 no-print">
        <li class="nav-item">
            <a class="nav-link {{ request('categoria_id') ? '' : 'active' }}"
               href="{{ route('inventario.productos.index', request()->except(['categoria_id', 'page'])) }}">Todos</a>
        </li>
        @foreach ($categorias->whereNull('padre_id') as $categoria)
            <li class="nav-item">
                <a class="nav-link {{ (int) request('categoria_id') === $categoria->id ? 'active' : '' }}"
                   href="{{ route('inventario.productos.index', array_merge(request()->except('page'), ['categoria_id' => $categoria->id])) }}">
                    {{ $categoria->nombre }}
                </a>
            </li>
        @endforeach
    </ul>

    <x-filter-bar placeholder="SKU, nombre, descripcion o codigo de barras...">
        <div class="col-md-3">
            <label class="form-label" for="filtro-categoria">Categoria</label>
            <select class="form-select" id="filtro-categoria" name="categoria_id">
                <option value="">Todas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((int) request('categoria_id') === $categoria->id)>
                        {{ $categoria->nombre_ruta }}
                    </option>
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
        <x-table :head="['SKU', 'Producto', 'Categoria', 'Unidad', ['label' => 'Costo', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Disponible', 'align' => 'end'], 'Estado', '']">
            @forelse ($productos as $producto)
                <tr>
                    <td class="text-muted">{{ $producto->sku }}</td>
                    <td>
                        <div class="fw-bold">{{ $producto->nombre }}</div>
                        @unless ($producto->es_inventariable)
                            <small class="text-muted">Servicio (sin inventario)</small>
                        @endunless
                    </td>
                    <td>{{ $producto->categoria?->nombre ?? '--' }}</td>
                    <td>{{ $producto->unidad?->codigo ?? '--' }}</td>
                    <td class="text-end">${{ number_format((float) $producto->costo, 2) }}</td>
                    <td class="text-end">${{ number_format((float) $producto->precio_venta, 2) }}</td>
                    <td class="text-end fw-bold">
                        @php $disponible = $existencias[$producto->id] ?? 0.0; @endphp
                        <span class="{{ $disponible <= 0 ? 'text-danger' : '' }}">{{ $disponible + 0 }}</span>
                    </td>
                    <td><x-badge :estado="$producto->estado->color()" :label="$producto->estado->label()" /></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info action-btn" href="{{ route('inventario.productos.show', $producto) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary action-btn" href="{{ route('inventario.productos.edit', $producto) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{ route('inventario.productos.destroy', $producto) }}"
                              data-confirm="Dar de baja {{ $producto->nombre }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="9" message="No hay productos en el catalogo." icon="box-seam" />
            @endforelse
        </x-table>

        {{ $productos->links() }}
    </x-card>
@endsection
