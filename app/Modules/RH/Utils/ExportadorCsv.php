<?php

declare(strict_types=1);

namespace App\Modules\RH\Utils;

use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga un reporte como CSV.
 *
 * Escribe directo a la salida en vez de armar el archivo en memoria, para que
 * un reporte de miles de renglones no se lleve el limite de PHP por delante.
 *
 * Lleva BOM de UTF-8 a proposito: sin el, Excel en Windows abre el archivo en
 * ANSI y los acentos y la enye salen rotos.
 *
 * Vive en el modulo porque Compartido todavia no publica su ExportService.
 * Cuando lo haga, esta clase se retira y los reportes llaman a aquel: el
 * formato del archivo es el mismo (PLANNING - "Import/Export").
 */
final class ExportadorCsv
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  iterable<int, array<int, mixed>>  $filas
     */
    public static function descargar(string $reporte, array $encabezados, iterable $filas): StreamedResponse
    {
        $archivo = sprintf('rh-%s-%s.csv', $reporte, Carbon::now()->format('Ymd'));

        return response()->streamDownload(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'wb');

            fwrite($salida, "\xEF\xBB\xBF");
            fwrite($salida, "sep=;\r\n");
            fputcsv($salida, $encabezados, ';');

            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ';');
            }

            fclose($salida);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
