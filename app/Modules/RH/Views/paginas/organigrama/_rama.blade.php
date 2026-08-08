{{--
    Una rama del organigrama de personas: el empleado y, debajo, quienes le
    reportan. Se llama a si misma, por eso vive en su propio parcial.

    Variables: $empleado, $empleadosPorJefe (colección agrupada por jefe_id).
--}}
<li>
    <div class="d-flex align-items-center gap-2 py-1">
        <i class="bi bi-person-circle text-muted"></i>
        <a href="{{ route('rh.empleados.show', $empleado) }}">{{ $empleado->nombre_completo }}</a>
        @if ($empleado->puesto)
            <small class="text-muted">{{ $empleado->puesto->nombre }}</small>
        @endif
    </div>

    @if (isset($empleadosPorJefe[$empleado->id]))
        <ul class="list-unstyled ps-4 border-start">
            @foreach ($empleadosPorJefe[$empleado->id] as $subordinado)
                @include('rh::paginas.organigrama._rama', [
                    'empleado' => $subordinado,
                    'empleadosPorJefe' => $empleadosPorJefe,
                ])
            @endforeach
        </ul>
    @endif
</li>
