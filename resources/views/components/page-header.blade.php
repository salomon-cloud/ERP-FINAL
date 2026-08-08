{{--
    <x-page-header> - title + subtitle + action buttons at the top of a page.

    Props:
      title    string       page title
      subtitle string|null  breadcrumb / context line
      slot                  action buttons (Nuevo, Imprimir, Exportar, ...)

    Usage:
      <x-page-header title="Facturas" subtitle="Ventas / Facturas">
          <a class="btn btn-primary" href="..."><i class="bi bi-plus-lg me-1"></i>Nueva</a>
      </x-page-header>
--}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'd-flex flex-wrap justify-content-between align-items-center gap-3 mb-3']) }}>
    <div>
        <h4 class="page-title mb-0">{{ $title }}</h4>
        @if ($subtitle)
            <small class="text-muted">{{ $subtitle }}</small>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="d-flex flex-wrap gap-2 no-print">{{ $slot }}</div>
    @endif
</div>
