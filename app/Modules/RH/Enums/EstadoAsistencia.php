<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * asistencias.estado. Los cuatro valores son los de SISEN v1 y los cuatro ya
 * tienen color propio en sisen.css, asi que color() devuelve el valor tal cual.
 *
 * `Permiso` lo pone ServicioAprobacionPermisos cuando aprueba una solicitud que
 * cae en ese dia: nadie lo captura a mano.
 */
enum EstadoAsistencia: string implements EstadoPresentable
{
    case Presente = 'presente';
    case Falta = 'falta';
    case Retardo = 'retardo';
    case Permiso = 'permiso';

    public function label(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Falta => 'Falta',
            self::Retardo => 'Retardo',
            self::Permiso => 'Permiso',
        };
    }

    public function color(): string
    {
        return $this->value;
    }

    /** Los estados en los que el empleado si se presento a trabajar. */
    public function cuentaComoAsistencia(): bool
    {
        return $this === self::Presente || $this === self::Retardo;
    }
}
