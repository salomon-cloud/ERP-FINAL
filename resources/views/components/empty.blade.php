{{--
    <x-empty> - empty state for lists and tables.

    Props:
      message string       what the user sees when there is nothing yet
      icon    string       bootstrap-icons name without the "bi-" prefix
      colspan int|null     when set, renders as a <tr><td colspan> so it can sit
                           inside <x-table>; otherwise renders as a block

    Every list page answers "is there data?" with this component - never with a
    silently empty table.
--}}
@props(['message' => 'No hay registros para mostrar.', 'icon' => 'inbox', 'colspan' => null, 'action' => null])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="text-center text-muted py-4">
            <i class="bi bi-{{ $icon }} d-block fs-3 mb-2"></i>
            {{ $message }}
            @if ($action)
                <div class="mt-2 no-print">{{ $action }}</div>
            @endif
        </td>
    </tr>
@else
    <div {{ $attributes->merge(['class' => 'text-center text-muted py-4']) }}>
        <i class="bi bi-{{ $icon }} d-block fs-3 mb-2"></i>
        {{ $message }}
        @if ($action)
            <div class="mt-2 no-print">{{ $action }}</div>
        @endif
    </div>
@endif
