<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * El estado activo|inactivo que comparten departamentos, puestos y empleados.
 *
 * Es un solo enum y no tres identicos porque los tres columnas guardan los
 * mismos dos valores y significan lo mismo. Los valores son los de la columna
 * enum('activo','inactivo') de SISEN v1, intactos.
 */
enum EstadoActivacion: string implements EstadoPresentable
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Inactivo => 'Inactivo',
        };
    }

    public function color(): string
    {
        return $this->value;
    }
}
