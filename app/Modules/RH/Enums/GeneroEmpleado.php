<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\Etiquetable;

/**
 * empleados.genero. Columna opcional: un empleado puede no declararlo.
 *
 * Valores segun chk_empleados_genero.
 */
enum GeneroEmpleado: string implements Etiquetable
{
    case Masculino = 'masculino';
    case Femenino = 'femenino';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
            self::Otro => 'Otro',
        };
    }
}
