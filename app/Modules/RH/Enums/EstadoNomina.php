<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * nominas.estado: el estado de UN recibo. Los tres valores son los de SISEN v1
 * y los tres ya tienen color en sisen.css.
 *
 * No confundir con EstadoNominaCorrida, que es el estado del proceso completo
 * que genera muchos recibos.
 */
enum EstadoNomina: string implements EstadoPresentable
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada => 'Pagada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return $this->value;
    }
}
