@extends('layouts.app')

@section('title', 'Nueva nota de credito')
@section('header', 'Nueva nota de credito')

@section('content')
    <x-page-header title="Nueva nota de credito" subtitle="Ventas / Notas de credito / Nueva" />

    <x-card>
        <form method="POST" action="{{ route('ventas.notas-credito.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="factura_id">Factura a corregir</label>
                    <select class="form-select @error('factura_id') is-invalid @enderror"
                            id="factura_id" name="factura_id" required>
                        <option value="">Selecciona...</option>
                        @foreach ($facturas as $factura)
                            <option value="{{ $factura->id }}" @selected((int) old('factura_id', $nota->factura_id) === $factura->id)>
                                {{ $factura->numero_factura }} - {{ $factura->cliente?->nombre }}
                                (${{ number_format((float) $factura->total, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('factura_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($facturas->isEmpty())
                        <small class="text-danger">No hay facturas emitidas contra las que hacer una nota.</small>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="motivo">Motivo</label>
                    <select class="form-select @error('motivo') is-invalid @enderror" id="motivo" name="motivo" required>
                        @foreach ($motivos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('motivo') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        Solo "devolucion de mercancia" regresa producto al almacen; las demas solo acreditan dinero.
                    </small>
                    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="fecha_emision">Emision</label>
                    <input class="form-control @error('fecha_emision') is-invalid @enderror" type="date"
                           id="fecha_emision" name="fecha_emision" required
                           value="{{ old('fecha_emision', now()->toDateString()) }}">
                    @error('fecha_emision') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('ventas.notas-credito.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Continuar</button>
            </div>
        </form>
    </x-card>
@endsection
