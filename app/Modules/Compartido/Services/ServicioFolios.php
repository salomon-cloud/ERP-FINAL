<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Services;

use App\Modules\Compartido\Models\SecuenciaDocumento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La unica forma de producir un folio visible.
 *
 *     $folio = app(ServicioFolios::class)->siguiente('pedidos');   // PED-000123
 *
 * A un usuario jamas se le muestra un id autoincremental, y un documento
 * cancelado nunca devuelve su folio al pozo (PLANNING - "Sequences").
 */
class ServicioFolios
{
    /**
     * Reserva y devuelve el siguiente folio de un contador.
     *
     * La fila se bloquea dentro de una transaccion, asi que dos peticiones
     * simultaneas no pueden recibir el mismo numero.
     */
    public function siguiente(string $modulo): string
    {
        return DB::transaction(function () use ($modulo): string {
            $secuencia = SecuenciaDocumento::query()
                ->where('modulo', $modulo)
                ->lockForUpdate()
                ->first();

            if ($secuencia === null) {
                throw new RuntimeException(
                    "No hay una secuencia configurada para [{$modulo}]. ".
                    'Agregala en database/seeders/SecuenciaDocumentoSeeder.php.'
                );
            }

            if (! $secuencia->activo) {
                throw new RuntimeException("La secuencia [{$modulo}] esta inactiva.");
            }

            $secuencia->numero_actual++;
            $secuencia->save();

            return $secuencia->formatear($secuencia->numero_actual);
        });
    }

    /** El folio que devolveria la siguiente llamada, sin consumirlo. */
    public function consultar(string $modulo): ?string
    {
        $secuencia = SecuenciaDocumento::query()->where('modulo', $modulo)->first();

        return $secuencia?->formatear($secuencia->numero_actual + 1);
    }
}
