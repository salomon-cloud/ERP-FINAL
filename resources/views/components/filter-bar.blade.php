{{--
    <x-filter-bar> - the global list filter contract.

    Props:
      placeholder string       hint for the search box
      search      string       request key for the search term (v1 uses "buscar")
      dates       bool         render the desde/hasta date range
      action      string|null  form target; defaults to the current URL
      slot                     extra <div class="col-md-*"> filters (selects, FKs)

    The controller reads the same keys back (buscar, desde, hasta, estado, *_id),
    applies them with ->when(...) and paginates with ->withQueryString(), so the
    bar repopulates itself. See PLANNING - "Global Filters".
--}}
@props(['placeholder' => 'Buscar...', 'search' => 'buscar', 'dates' => false, 'action' => null])

<form method="GET" action="{{ $action ?? url()->current() }}"
    {{ $attributes->merge(['class' => 'row g-2 align-items-end mb-3 no-print']) }}>

    <div class="col-md-4">
        <label class="form-label" for="filter-{{ $search }}">Buscar</label>
        <input class="form-control" type="search" id="filter-{{ $search }}" name="{{ $search }}"
            value="{{ request($search) }}" placeholder="{{ $placeholder }}">
    </div>

    @if ($dates)
        <div class="col-md-2">
            <label class="form-label" for="filter-desde">Desde</label>
            <input class="form-control" type="date" id="filter-desde" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filter-hasta">Hasta</label>
            <input class="form-control" type="date" id="filter-hasta" name="hasta" value="{{ request('hasta') }}">
        </div>
    @endif

    {{ $slot }}

    <div class="col-md-auto d-flex gap-2">
        <button class="btn btn-outline-primary" type="submit">
            <i class="bi bi-funnel me-1"></i>Filtrar
        </button>
        <a class="btn btn-outline-secondary" href="{{ url()->current() }}">Limpiar</a>
    </div>
</form>
