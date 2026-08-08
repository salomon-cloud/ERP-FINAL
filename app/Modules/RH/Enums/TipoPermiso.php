<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\Etiquetable;

/**
 * permisos.tipo, con los tres valores de SISEN v1.
 *
 * Que el permiso se pague o no NO se deduce del tipo: vive en la columna
 * permisos.con_goce, porque un mismo tipo puede ser con o sin goce segun el
 * caso.
 */
enum TipoPermiso: string implements Etiquetable
{
    case Permiso = 'permiso';
    case Vacaciones = 'vacaciones';
    case Incapacidad = 'incapacidad';

    public function label(): string
    {
        return match ($this) {
            self::Permiso => 'Permiso',
            self::Vacaciones => 'Vacaciones',
            self::Incapacidad => 'Incapacidad',
        };
    }
}
