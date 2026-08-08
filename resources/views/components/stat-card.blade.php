{{--
    <x-stat-card> - KPI card for module dashboards.

    Props:
      label string       what is being counted
      value string|int   the number (already formatted by the controller)
      icon  string       bootstrap-icons name without the "bi-" prefix
      href  string|null  where the KPI drills down to (every KPI must link to
                         the list page that proves it)
      hint  string|null  small clarifying line

    KPIs are always real queries computed in the controller - never fabricated.
--}}
@props(['label', 'value', 'icon' => 'graph-up', 'href' => null, 'hint' => null])

<div {{ $attributes->merge(['class' => 'soft-card stat-card h-100']) }}>
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
            <div class="text-muted small text-uppercase fw-bold">{{ $label }}</div>
            <div class="fs-3 fw-bold">{{ $value }}</div>
            @if ($hint)
                <small class="text-muted">{{ $hint }}</small>
            @endif
        </div>
        <div class="stat-icon"><i class="bi bi-{{ $icon }}"></i></div>
    </div>

    @if ($href)
        <a class="quick-link small fw-bold no-print" href="{{ $href }}">
            Ver detalle <i class="bi bi-arrow-right"></i>
        </a>
    @endif
</div>
