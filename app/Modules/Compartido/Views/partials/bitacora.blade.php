{{--
    La cronologia de un documento, tal como quedo en bitacora_auditoria.

    La pintan las fichas de los tres modulos ERP, por eso vive en Compartido y
    no repetida en cada uno. Los traits TieneBitacora y TieneCamposAuditoria ya
    llenaron estas filas; aqui solo se leen.

    Variable: $bitacora (coleccion de RegistroBitacora).
--}}
<x-card title="Cronologia" subtitle="Quien hizo que y cuando">
    @forelse ($bitacora as $registro)
        <div class="d-flex justify-content-between align-items-start gap-2 py-2 border-bottom">
            <div>
                <span class="fw-bold text-capitalize">{{ str_replace('_', ' ', $registro->accion) }}</span>
                <div class="small text-muted">{{ $registro->usuario?->name ?? 'Sistema' }}</div>
            </div>
            <span class="small text-muted text-nowrap">{{ $registro->created_at?->format('d/m/Y H:i') }}</span>
        </div>
    @empty
        <x-empty message="Sin actividad registrada." icon="clock-history" />
    @endforelse
</x-card>
