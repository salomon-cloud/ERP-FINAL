<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * traspasos.estado, segun chk_traspasos_estado.
 *
 *   borrador -> en_transito -> recibido
 *       \_______ cancelado
 *
 * `en_transito` ya descargo el almacen de origen; a partir de ahi el traspaso
 * no se cancela, se recibe. La mercancia existe y esta en algun lado.
 */
enum EstadoTraspaso: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case EnTransito = 'en_transito';
    case Recibido = 'recibido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnTransito => 'En transito',
            self::Recibido => 'Recibido',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::EnTransito => 'permiso',
            self::Recibido => 'aprobado',
            self::Cancelado => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    public function esCancelable(): bool
    {
        return $this === self::Borrador;
    }
}
