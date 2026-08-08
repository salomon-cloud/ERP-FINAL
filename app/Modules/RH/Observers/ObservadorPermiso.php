<?php

declare(strict_types=1);

namespace App\Modules\RH\Observers;

use App\Modules\RH\Models\Permiso;

/**
 * Deriva `dias` del rango de fechas de la solicitud.
 *
 * Cuenta dias naturales con los dos extremos incluidos: del 10 al 14 son 5.
 *
 * Solo calcula cuando quien guarda no dijo nada. Un valor explicito se respeta,
 * porque la columna es decimal y admite medios dias (0.5), y porque mas adelante
 * ServicioAprobacionPermisos puede aplicar una politica de dias habiles que
 * excluya fines de semana. Si esa politica llega, se implementa una sola vez y
 * este observer sigue siendo el valor por omision.
 */
class ObservadorPermiso
{
    public function saving(Permiso $permiso): void
    {
        if ((float) $permiso->dias > 0) {
            return;
        }

        if (blank($permiso->fecha_inicio) || blank($permiso->fecha_fin)) {
            return;
        }

        // chk_permisos_fechas ya garantiza fecha_fin >= fecha_inicio.
        $permiso->dias = (int) $permiso->fecha_inicio->diffInDays($permiso->fecha_fin) + 1;
    }
}
