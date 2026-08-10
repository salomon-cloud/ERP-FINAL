@extends('layouts.app')

@section('title', 'Conteo fisico')
@section('header', $conteo->numero_conteo)
@section('subtitle', 'Conteo fisico')

@php
    use App\Modules\Inventario\Enums\EstadoConteo;

    $conDiferencia = $conteo->lineas->filter(fn ($l) => abs((float) $l->diferencia) > 0.000001);
@endphp

@section('content')
    <x-page-header :title="$conteo->numero_conteo" subtitle="Inventario / Conteos / Detalle">
        @if ($conteo->estado === EstadoConteo::Borrador)
            <a class="btn btn-outline-primary" href="{{ route('inventario.conteos.edit', $conteo) }}">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST" action="{{ route('inventario.conteos.iniciar', $conteo) }}"
                  data-confirm="Iniciar el conteo? Se congelara la existencia esperada de cada producto.">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-play-fill me-1"></i>Iniciar conteo</button>
            </form>
        @elseif (in_array($conteo->estado, [EstadoConteo::Contado, EstadoConteo::Ajustado], true))
            @can('inventario.conteos.cerrar')
                <form method="POST" action="{{ route('inventario.conteos.cerrar', $conteo) }}"
                      data-confirm="Cerrar el conteo? Las diferencias se convertiran en movimientos de inventario.">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Cerrar y ajustar</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold">{{ $conteo->numero_conteo }}</div>
                        <div class="text-muted">
                            {{ $conteo->almacen?->nombre ?? '--' }}
                            @if ($conteo->ubicacion) · {{ $conteo->ubicacion->codigo }} @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <x-badge :estado="$conteo->estado->color()" :label="$conteo->estado->label()" class="fs-6" />
                        @if ($conDiferencia->isNotEmpty())
                            <div class="small text-muted mt-2">{{ $conDiferencia->count() }} linea(s) con diferencia</div>
                        @endif
                    </div>
                </div>

                @if ($conteo->estado === EstadoConteo::Borrador)
                    <x-empty message="Inicia el conteo para cargar los productos del almacen." icon="play-circle" />
                @else
                    <form method="POST" action="{{ route('inventario.conteos.capturar', $conteo) }}">
                        @csrf

                        <x-table :head="['Producto', ['label' => 'Esperado', 'align' => 'end'], ['label' => 'Contado', 'align' => 'end'], ['label' => 'Diferencia', 'align' => 'end']]">
                            @forelse ($conteo->lineas as $linea)
                                <tr>
                                    <td>
                                        <div>{{ $linea->producto?->nombre ?? '--' }}</div>
                                        <small class="text-muted">{{ $linea->producto?->sku }}</small>
                                    </td>
                                    <td class="text-end">{{ (float) $linea->cantidad_esperada }}</td>
                                    <td class="text-end" style="max-width: 140px">
                                        @if ($conteo->estado->admiteCaptura())
                                            <input class="form-control form-control-sm text-end" type="number"
                                                   step="0.000001" min="0"
                                                   name="cantidades[{{ $linea->id }}]"
                                                   value="{{ $linea->cantidad_contada !== null ? (float) $linea->cantidad_contada : '' }}">
                                        @else
                                            {{ $linea->cantidad_contada !== null ? (float) $linea->cantidad_contada : '--' }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold {{ (float) $linea->diferencia < 0 ? 'text-danger' : ((float) $linea->diferencia > 0 ? 'text-success' : 'text-muted') }}">
                                        {{ (float) $linea->diferencia > 0 ? '+' : '' }}{{ (float) $linea->diferencia }}
                                    </td>
                                </tr>
                            @empty
                                <x-empty :colspan="4" message="El almacen no tenia productos con existencia al iniciar." icon="ui-checks" />
                            @endforelse
                        </x-table>

                        @if ($conteo->estado->admiteCaptura())
                            <div class="d-flex justify-content-end mt-3 no-print">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-save me-1"></i>Guardar lo contado
                                </button>
                            </div>
                        @endif
                    </form>

                    @if ($conteo->estado->admiteCaptura())
                        <form class="row g-2 align-items-end mt-3 pt-3 border-top no-print" method="POST"
                              action="{{ route('inventario.conteos.productos.store', $conteo) }}">
                            @csrf
                            <div class="col-md-9">
                                <label class="form-label" for="producto_id">Aparecio un producto que no estaba en la lista</label>
                                <select class="form-select" id="producto_id" name="producto_id" required>
                                    <option value="">Selecciona...</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100" type="submit">
                                    <i class="bi bi-plus-lg"></i> Agregar al conteo
                                </button>
                            </div>
                        </form>
                    @endif
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Seguimiento" class="mb-3">
                <dl class="row mb-0">
                    <dt class="col-6">Conto</dt><dd class="col-6">{{ $conteo->contadoPor?->name ?? '--' }}</dd>
                    <dt class="col-6">Contado</dt><dd class="col-6">{{ $conteo->contado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                    <dt class="col-6">Cerro</dt><dd class="col-6">{{ $conteo->cerradoPor?->name ?? '--' }}</dd>
                    <dt class="col-6">Cerrado</dt><dd class="col-6">{{ $conteo->cerrado_en?->format('d/m/Y H:i') ?? '--' }}</dd>
                </dl>

                @if ($conteo->estado->admiteCaptura())
                    <div class="alert alert-info small mt-3 mb-0">
                        La existencia esperada quedo congelada al iniciar. Aunque se venda algo mientras
                        cuentas, la diferencia sigue significando lo mismo.
                    </div>
                @endif
            </x-card>

            @include('compartido::partials.bitacora')
        </div>
    </div>
@endsection
