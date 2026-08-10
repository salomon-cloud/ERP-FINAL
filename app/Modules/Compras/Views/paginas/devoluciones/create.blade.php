@extends('layouts.app')

@section('title', 'Nueva devolucion')
@section('header', 'Nueva devolucion a proveedor')

@section('content')
    <x-page-header title="Nueva devolucion a proveedor" subtitle="Compras / Devoluciones / Nueva" />

    <x-card subtitle="Se devuelve contra una factura ya contabilizada: lo que se reclama es dinero facturado.">
        <form method="POST" action="{{ route('compras.devoluciones.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="factura_proveedor_id">Factura de proveedor</label>
                    <select class="form-select @error('factura_proveedor_id') is-invalid @enderror"
                            id="factura_proveedor_id" name="factura_proveedor_id" required>
                        <option value="">Selecciona...</option>
                        @foreach ($facturas as $factura)
                            <option value="{{ $factura->id }}" @selected((int) old('factura_proveedor_id', $devolucion->factura_proveedor_id) === $factura->id)>
                                {{ $factura->numero_factura }} - {{ $factura->proveedor?->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('factura_proveedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($facturas->isEmpty())
                        <small class="text-danger">No hay facturas contabilizadas contra las que devolver.</small>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="motivo">Motivo</label>
                    <select class="form-select @error('motivo') is-invalid @enderror" id="motivo" name="motivo" required>
                        @foreach ($motivos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('motivo') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
                           required value="{{ old('fecha', now()->toDateString()) }}">
                    @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('compras.devoluciones.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Continuar</button>
            </div>
        </form>
    </x-card>
@endsection
