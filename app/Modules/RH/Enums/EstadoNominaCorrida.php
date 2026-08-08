<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * nomina_corridas.estado, segun chk_nomina_corrida_estado.
 *
 * Es el ciclo de vida de documento del Apendice A.2:
 *
 *   borrador -> procesada -> aplicada
 *      \____________/
 *            cancelada
 *
 * `aplicada` es el punto de no retorno: genero la poliza en Finanzas, asi que
 * la corrida ya no se edita ni se cancela; se corrige con una reversion.
 */
enum EstadoNominaCorrida: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Procesada = 'procesada';
    case Aplicada = 'aplicada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Procesada => 'Procesada',
            self::Aplicada => 'Aplicada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Procesada => 'permiso',
            self::Aplicada => 'aprobado',
            self::Cancelada => 'cancelada',
        };
    }

    /** Solo un borrador se sigue editando. */
    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Aplicada ya toco Finanzas; cancelada ya termino. */
    public function esCancelable(): bool
    {
        return $this === self::Borrador || $this === self::Procesada;
    }
}
