@extends('layouts.app')

@section('title', 'Lista de precios')
@section('header', $lista->nombre)
@section('subtitle', $lista->codigo)

@section('content')
    <x-page-header :title="$lista->nombre" :subtitle="'Ventas / Listas de precios / '.$lista->codigo">
        <a class="btn btn-primary" href="{{ route('ventas.listas-precios.edit', $lista) }}">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </x-page-header>

    <x-card>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <div class="fs-4 fw-bold">{{ $lista->nombre }}</div>
                <div class="text-muted">{{ $lista->codigo }} · {{ $lista->moneda }}</div>
            </div>
            @if ($lista->es_predeterminada)
                <span class="badge-soft badge-aprobado"><i class="bi bi-star-fill me-1"></i>Predeterminada</span>
            @endif
        </div>

        <p class="text-muted small">
            Un mismo producto puede tener varios escalones: 100 pesos a partir de 1 pieza y 85 a partir de 50.
            Al cotizar se toma el escalon mas alto que la cantidad alcanza.
        </p>

        <x-table :head="['SKU', 'Producto', ['label' => 'A partir de', 'align' => 'end'], ['label' => 'Precio', 'align' => 'end'], ['label' => 'Precio de catalogo', 'align' => 'end'], '']">
            @forelse ($lista->items->sortBy([['producto.nombre', 'asc'], ['cantidad_minima', 'asc']]) as $item)
                <tr>
                    <td class="text-muted">{{ $item->producto?->sku }}</td>
                    <td>{{ $item->producto?->nombre }}</td>
                    <td class="text-end">{{ (float) $item->cantidad_minima }}</td>
                    <td class="text-end fw-bold">${{ number_format((float) $item->precio, 2) }}</td>
                    <td class="text-end text-muted">${{ number_format((float) ($item->producto?->precio_venta ?? 0), 2) }}</td>
                    <td class="text-end">
                        <form class="d-inline" method="POST"
                              action="{{ route('ventas.listas-precios.items.destroy', [$lista, $item]) }}"
                              data-confirm="Quitar este precio de la lista?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty :colspan="6" message="Esta lista todavia no tiene precios." icon="tags" />
            @endforelse
        </x-table>

        <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
              action="{{ route('ventas.listas-precios.items.store', $lista) }}">
            @csrf
            <div class="col-md-5">
                <label class="form-label" for="producto_id">Producto</label>
                <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
                    <option value="">Selecciona...</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                    @endforeach
                </select>
                @error('producto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cantidad_minima">A partir de</label>
                <input class="form-control @error('cantidad_minima') is-invalid @enderror" type="number"
                       step="0.000001" min="0.000001" id="cantidad_minima" name="cantidad_minima" value="1" required>
                @error('cantidad_minima') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="precio">Precio</label>
                <input class="form-control @error('precio') is-invalid @enderror" type="number"
                       step="0.01" min="0" id="precio" name="precio" required>
                @error('precio') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            </div>
        </form>
    </x-card>
@endsection
