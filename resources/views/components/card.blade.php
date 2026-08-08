{{--
    <x-card> - the soft-card wrapper used by every content block in SISEN.

    Props:
      title    string|null  optional header title
      subtitle string|null  optional muted line under the title
      actions  slot         optional buttons rendered at the right of the header

    Usage:
      <x-card title="Polizas recientes">
          <x-slot:actions><a class="btn btn-sm btn-outline-primary">Ver todas</a></x-slot:actions>
          ...
      </x-card>
--}}
@props(['title' => null, 'subtitle' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'soft-card p-3']) }}>
    @if ($title || $actions)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                @if ($title)
                    <h5 class="fw-bold mb-0">{{ $title }}</h5>
                @endif
                @if ($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if ($actions)
                <div class="d-flex flex-wrap gap-2 no-print">{{ $actions }}</div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
