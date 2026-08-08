<?php

declare(strict_types=1);

namespace App\Modules\RH\Services;

use App\Models\User;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Permiso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La decision sobre una solicitud de permiso, y sus consecuencias.
 *
 * Aprobar no es solo cambiar una columna: marca los dias del rango en
 * asistencia y, si el permiso es sin goce, esos dias se descuentan en la
 * nomina. Por eso vive en un servicio y no en un controlador.
 */
class ServicioAprobacionPermisos
{
    public function __construct(private readonly ServicioAsistencia $asistencias) {}

    /**
     * Aprueba la solicitud y marca sus dias en asistencia.
     *
     * @throws RuntimeException si la solicitud ya fue revisada
     */
    public function aprobar(Permiso $permiso, User $revisor, ?string $comentario = null): Permiso
    {
        return DB::transaction(function () use ($permiso, $revisor, $comentario): Permiso {
            $this->exigirPendiente($permiso);

            $permiso->update([
                'estado' => EstadoPermiso::Aprobado,
                'revisado_por' => $revisor->id,
                'revisado_en' => now(),
                'comentario_revision' => $comentario,
            ]);

            // La linea de tiempo debe decir "aprobado", no "actualizado".
            $permiso->registrarBitacora('aprobado', [], ['estado' => EstadoPermiso::Aprobado->value]);

            $this->marcarDiasEnAsistencia($permiso);

            return $permiso;
        });
    }

    /**
     * Rechaza la solicitud. No toca asistencia: un permiso rechazado no existio.
     *
     * @throws RuntimeException si la solicitud ya fue revisada
     */
    public function rechazar(Permiso $permiso, User $revisor, ?string $comentario = null): Permiso
    {
        $this->exigirPendiente($permiso);

        $permiso->update([
            'estado' => EstadoPermiso::Rechazado,
            'revisado_por' => $revisor->id,
            'revisado_en' => now(),
            'comentario_revision' => $comentario,
        ]);

        $permiso->registrarBitacora('rechazado', [], ['estado' => EstadoPermiso::Rechazado->value]);

        return $permiso;
    }

    /**
     * Dias sin goce de sueldo que un empleado tuvo dentro de un periodo.
     *
     * Es lo que ServicioCorridaNomina descuenta del recibo. Solo cuentan los
     * permisos aprobados y sin goce, y solo la parte del permiso que cae dentro
     * del periodo: unas vacaciones a caballo entre dos quincenas se reparten.
     */
    public function diasSinGoce(Empleado $empleado, Carbon|string $desde, Carbon|string $hasta): float
    {
        $inicio = Carbon::parse($desde)->startOfDay();
        $fin = Carbon::parse($hasta)->startOfDay();

        return Permiso::query()
            ->where('empleado_id', $empleado->id)
            ->where('estado', EstadoPermiso::Aprobado)
            ->where('con_goce', false)
            ->queCruzan($inicio->toDateString(), $fin->toDateString())
            ->get()
            ->sum(function (Permiso $permiso) use ($inicio, $fin): int {
                $desdeReal = $permiso->fecha_inicio->max($inicio);
                $hastaReal = $permiso->fecha_fin->min($fin);

                return (int) $desdeReal->diffInDays($hastaReal) + 1;
            });
    }

    /** @throws RuntimeException */
    private function exigirPendiente(Permiso $permiso): void
    {
        if ($permiso->estado->esFinal()) {
            throw new RuntimeException(
                'La solicitud ya fue revisada ('.$permiso->estado->label().') y no se puede volver a decidir.'
            );
        }
    }

    /** Marca como permiso cada dia del rango que el empleado no haya trabajado. */
    private function marcarDiasEnAsistencia(Permiso $permiso): void
    {
        $empleado = $permiso->empleado;

        if ($empleado === null) {
            return;
        }

        foreach ($permiso->fecha_inicio->toPeriod($permiso->fecha_fin) as $dia) {
            $this->asistencias->marcarComoPermiso($empleado, $dia);
        }
    }
}
