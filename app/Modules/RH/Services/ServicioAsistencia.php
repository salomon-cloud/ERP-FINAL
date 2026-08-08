<?php

declare(strict_types=1);

namespace App\Modules\RH\Services;

use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Empleado;
use Illuminate\Support\Carbon;

/**
 * Entradas, salidas y el resumen que la nomina necesita.
 *
 * Las horas trabajadas NO se calculan aqui: las deriva ObservadorAsistencia al
 * guardar. Este servicio decide lo que el observer no puede saber -- si una
 * entrada llego tarde, que dias hay que marcar como permiso -- y responde el
 * resumen del periodo para que ServicioCorridaNomina no tenga que repetir las
 * consultas.
 */
class ServicioAsistencia
{
    /**
     * Registra la entrada del dia. Si ya habia registro, lo actualiza: el
     * indice uq_asistencias_empleado_fecha solo admite uno por empleado y dia.
     *
     * `$horaEsperada` es la hora en que ese empleado debia entrar. Se recibe
     * como parametro porque el esquema todavia no modela horarios ni turnos
     * (no hay tabla de jornadas en PLANNING). Sin ella no se puede saber si
     * hubo retardo, asi que el registro queda como presente.
     */
    public function registrarEntrada(
        Empleado $empleado,
        Carbon|string $fecha,
        string $hora,
        ?string $horaEsperada = null,
    ): Asistencia {
        $estado = $this->clasificarEntrada($hora, $horaEsperada);

        return Asistencia::updateOrCreate(
            ['empleado_id' => $empleado->id, 'fecha' => Carbon::parse($fecha)->toDateString()],
            ['hora_entrada' => $hora, 'estado' => $estado],
        );
    }

    /** Cierra el dia. El observer recalcula las horas al guardar. */
    public function registrarSalida(Asistencia $asistencia, string $hora): Asistencia
    {
        $asistencia->update(['hora_salida' => $hora]);

        return $asistencia;
    }

    /**
     * Marca un dia como permiso. Lo llama ServicioAprobacionPermisos cuando
     * aprueba una solicitud.
     *
     * Si el empleado de hecho se presento ese dia, gana el registro real: una
     * solicitud aprobada no puede borrar el hecho de que la persona trabajo.
     */
    public function marcarComoPermiso(Empleado $empleado, Carbon|string $fecha): ?Asistencia
    {
        $dia = Carbon::parse($fecha)->toDateString();

        $existente = Asistencia::query()
            ->where('empleado_id', $empleado->id)
            ->whereDate('fecha', $dia)
            ->first();

        if ($existente !== null && $existente->estado->cuentaComoAsistencia()) {
            return null;
        }

        return Asistencia::updateOrCreate(
            ['empleado_id' => $empleado->id, 'fecha' => $dia],
            ['estado' => EstadoAsistencia::Permiso],
        );
    }

    /**
     * Lo que la nomina necesita saber de un empleado en un periodo, en una sola
     * consulta agrupada.
     *
     * @return array{dias_trabajados: int, faltas: int, retardos: int, horas_trabajadas: float}
     */
    public function resumenDelPeriodo(Empleado $empleado, Carbon|string $desde, Carbon|string $hasta): array
    {
        $conteos = Asistencia::query()
            ->where('empleado_id', $empleado->id)
            ->entreFechas(Carbon::parse($desde)->toDateString(), Carbon::parse($hasta)->toDateString())
            ->selectRaw('estado, COUNT(*) AS dias, COALESCE(SUM(horas_trabajadas), 0) AS horas')
            ->groupBy('estado')
            ->get()
            ->keyBy(fn (Asistencia $fila) => $fila->estado->value);

        $dias = fn (string $estado): int => (int) ($conteos[$estado]->dias ?? 0);

        return [
            'dias_trabajados' => $dias(EstadoAsistencia::Presente->value) + $dias(EstadoAsistencia::Retardo->value),
            'faltas' => $dias(EstadoAsistencia::Falta->value),
            'retardos' => $dias(EstadoAsistencia::Retardo->value),
            'horas_trabajadas' => (float) $conteos->sum('horas'),
        ];
    }

    /**
     * Presente, o retardo si la entrada rebaso la tolerancia.
     *
     * La tolerancia sale de config('sisen.hr.late_threshold_minutes').
     */
    private function clasificarEntrada(string $hora, ?string $horaEsperada): EstadoAsistencia
    {
        if ($horaEsperada === null) {
            return EstadoAsistencia::Presente;
        }

        $tolerancia = (int) config('sisen.hr.late_threshold_minutes', 15);
        $limite = Carbon::parse($horaEsperada)->addMinutes($tolerancia);

        return Carbon::parse($hora)->greaterThan($limite)
            ? EstadoAsistencia::Retardo
            : EstadoAsistencia::Presente;
    }
}
