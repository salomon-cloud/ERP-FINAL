<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\Etiquetable;

/**
 * Cada cuanto se le paga a alguien. La comparten empleados.frecuencia_pago y
 * nomina_periodos.frecuencia, que tienen el mismo CHECK.
 *
 * La quincena es el valor por omision en los dos lados, igual que en SISEN v1.
 */
enum FrecuenciaPago: string implements Etiquetable
{
    case Semanal = 'semanal';
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';

    public function label(): string
    {
        return match ($this) {
            self::Semanal => 'Semanal',
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
        };
    }

    /** Cuantos periodos de esta frecuencia caben en un ano. */
    public function periodosPorAno(): int
    {
        return match ($this) {
            self::Semanal => 52,
            self::Quincenal => 24,
            self::Mensual => 12,
        };
    }
}
