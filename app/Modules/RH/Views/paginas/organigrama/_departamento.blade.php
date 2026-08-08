{{--
    Una rama del arbol de departamentos, con los que dependen de el.

    Variables: $departamento, $departamentosPorPadre, $empleadosPorDepartamento.
--}}
<li class="mb-2">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <i class="bi bi-building text-muted"></i>
        <a class="fw-bold" href="{{ route('rh.departamentos.show', $departamento) }}">{{ $departamento->nombre }}</a>
        <span class="badge text-bg-light">
            {{ count($empleadosPorDepartamento[$departamento->id] ?? []) }} empleados
        </span>
        @if ($departamento->jefe)
            <small class="text-muted">Jefe: {{ $departamento->jefe->nombre_completo }}</small>
        @endif
    </div>

    @if (isset($departamentosPorPadre[$departamento->id]))
        <ul class="list-unstyled ps-4 border-start mt-2">
            @foreach ($departamentosPorPadre[$departamento->id] as $hijo)
                @include('rh::paginas.organigrama._departamento', [
                    'departamento' => $hijo,
                    'departamentosPorPadre' => $departamentosPorPadre,
                    'empleadosPorDepartamento' => $empleadosPorDepartamento,
                ])
            @endforeach
        </ul>
    @endif
</li>
