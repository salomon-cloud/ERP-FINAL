{{--
    <x-badge> - status pill using the SISEN colour map from public/css/sisen.css.

    Props:
      estado string       the raw state value (activo, pagada, pendiente, ...)
      label  string|null  visible text; defaults to the humanised state

    The colour comes from .badge-{estado}. When a module introduces a new state,
    add its colour to sisen.css - never hardcode a colour in a template.
    Same output as the v1 partials/badge, so both can coexist.
--}}
@props(['estado', 'label' => null])

<span {{ $attributes->merge(['class' => 'badge-soft badge-'.$estado]) }}>
    {{ $label ?? ucfirst(str_replace('_', ' ', $estado)) }}
</span>
