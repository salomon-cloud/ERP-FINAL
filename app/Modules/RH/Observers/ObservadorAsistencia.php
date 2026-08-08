<?php

declare(strict_types=1);

namespace App\Modules\RH\Observers;

use App\Modules\RH\Models\Asistencia;
use Illuminate\Support\Carbon;

/**
 * Deriva `horas_trabajadas` de la entrada y la salida.
 *
 * Vive aqui y no en un controlador ni en un Service para que el numero sea
 * correcto venga de donde venga el registro: la pantalla de captura, la carga
 * masiva del checador o ServicioAsistencia. Ninguno de los tres repite la
 * formula.
 *
 * Si falta alguna de las dos horas no se toca el valor: un dia de falta o de
 * permiso puede llevar las horas que RH decida (normalmente 0).
 */
class ObservadorAsistencia
{
    public function saving(Asistencia $asistencia): void
    {
        if (blank($asistencia->hora_entrada) || blank($asistencia->hora_salida)) {
            return;
        }

        $entrada = Carbon::parse((string) $asistencia->hora_entrada);
        $salida = Carbon::parse((string) $asistencia->hora_salida);

        // Una salida anterior a la entrada la rechaza chk_asistencias_horario;
        // el max() solo evita mandarle a la base un negativo que ya sabemos malo.
        $segundos = max(0, $salida->getTimestamp() - $entrada->getTimestamp());

        $asistencia->horas_trabajadas = round($segundos / 3600, 2);
    }
}
