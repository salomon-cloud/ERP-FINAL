@extends('layouts.app')

@section('title', 'Nueva recepcion')
@section('header', 'Nueva recepcion')

@section('content')
    <x-page-header title="Nueva recepcion" subtitle="Compras / Recepciones / Nueva" />

    <x-card subtitle="Solo aparecen las ordenes confirmadas o a medio recibir.">
        <form method="POST" action="{{ route('compras.recepciones.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="orden_compra_id">Orden de compra</label>
                    <select class="form-select @error('orden_compra_id') is-invalid @enderror"
                            id="orden_compra_id" name="orden_compra_id" required>
                        <option value="">Selecciona...</option>
                        @foreach ($ordenes as $orden)
                            <option value="{{ $orden->id }}" @selected((int) old('orden_compra_id', $recepcion->orden_compra_id) === $orden->id)>
                                {{ $orden->numero_orden }} - {{ $orden->proveedor?->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('orden_compra_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($ordenes->isEmpty())
                        <small class="text-danger">
                            No hay ordenes confirmadas. Confirma una orden antes de recibir contra ella.
                        </small>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="almacen_id">Almacen que recibe</label>
                    <select class="form-select @error('almacen_id') is-invalid @enderror" id="almacen_id" name="almacen_id" required>
                        <option value="">Selecciona...</option>
                        @foreach ($almacenes as $almacen)
                            <option value="{{ $almacen->id }}" @selected((int) old('almacen_id') === $almacen->id)>
                                {{ $almacen->codigo }} - {{ $almacen->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input class="form-control @error('fecha') is-invalid @enderror" type="date" id="fecha" name="fecha"
                           required value="{{ old('fecha', now()->toDateString()) }}">
                    @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('compras.recepciones.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Continuar</button>
            </div>
        </form>
    </x-card>
@endsection
