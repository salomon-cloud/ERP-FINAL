@extends('layouts.app')

@section('title', 'Nueva corrida')
@section('header', 'Nueva corrida de nomina')

@section('content')
    <x-page-header title="Nueva corrida" subtitle="RH / Nomina / Corridas / Nueva" />

    <x-card subtitle="La corrida nace en borrador. Los recibos se generan al procesarla.">
        <form method="POST" action="{{ route('rh.nomina-corridas.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="periodo_id">Periodo a procesar</label>
                    <select class="form-select @error('periodo_id') is-invalid @enderror" id="periodo_id"
                            name="periodo_id" required>
                        <option value="">Selecciona...</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((int) old('periodo_id') === $periodo->id)>
                                {{ $periodo->codigo_periodo }}
                                ({{ $periodo->fecha_inicio->format('d/m/Y') }} - {{ $periodo->fecha_fin->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Solo se listan los periodos abiertos.</small>
                    @error('periodo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('rh.nomina-corridas.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Crear corrida</button>
            </div>
        </form>
    </x-card>
@endsection
