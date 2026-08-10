{{--
    Los dos botones que lleva todo reporte: imprimir y exportar.

    El enlace de CSV conserva los filtros actuales, de modo que el archivo trae
    exactamente lo que esta en pantalla y no el reporte completo.

    Variable: $ruta (nombre de la ruta del reporte).
--}}
<button class="btn btn-outline-secondary" onclick="window.print()">
    <i class="bi bi-printer me-1"></i>Imprimir
</button>

<a class="btn btn-outline-primary" href="{{ route($ruta, array_merge(request()->query(), ['formato' => 'csv'])) }}">
    <i class="bi bi-filetype-csv me-1"></i>Exportar CSV
</a>
