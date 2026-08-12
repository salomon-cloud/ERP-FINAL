<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Support;

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
 * Es la version compartida del ExportadorCsv que RH declaro dentro de su modulo
 * cuando era el unico exportando. El formato del archivo es identico, asi que
 * los CSV de RH y los de Ventas/Compras/Inventario se abren igual.
 */
final class ExportadorCsv
{
    /**
     * @param  string  $modulo  prefijo del nombre de archivo (ventas, compras, ...)
     * @param  array<int, string>  $encabezados
     * @param  iterable<int, array<int, mixed>>  $filas
     */
    public static function descargar(string $modulo, string $reporte, array $encabezados, iterable $filas): StreamedResponse
    {
        $archivo = sprintf('%s-%s-%s.csv', $modulo, $reporte, Carbon::now()->format('Ymd'));

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
