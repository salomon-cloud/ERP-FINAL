{{--
    <x-table> - the standard responsive list table.

    Props:
      head array  column headers. Each entry is either a string, or an array
                  ['label' => 'Total', 'align' => 'end', 'width' => '120px'].
                  Numeric columns are right aligned with align => 'end'.
      slot        the <tr> rows (use <x-empty :colspan="..."> for the empty case)

    Usage:
      <x-table :head="['Numero', 'Cliente', ['label' => 'Total', 'align' => 'end'], '']">
          @forelse ($rows as $row) ... @empty <x-empty :colspan="4" /> @endforelse
      </x-table>
--}}
@props(['head' => []])

<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'table align-middle']) }}>
        @if (! empty($head))
            <thead>
                <tr>
                    @foreach ($head as $column)
                        @php
                            $column = is_array($column) ? $column : ['label' => $column];
                            $align = $column['align'] ?? 'start';
                        @endphp
                        <th class="text-{{ $align }}" @if (! empty($column['width'])) style="width: {{ $column['width'] }}" @endif>
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
