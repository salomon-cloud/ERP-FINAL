<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * permisos.estado, con los tres valores de SISEN v1.
 *
 * Solo ServicioAprobacionPermisos mueve este estado; un controlador jamas lo
 * asigna directo.
 */
enum EstadoPermiso: string implements EstadoPresentable
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function color(): string
    {
        return $this->value;
    }

    /** Una solicitud ya revisada no se vuelve a revisar ni se edita. */
    public function esFinal(): bool
    {
        return $this !== self::Pendiente;
    }
}
