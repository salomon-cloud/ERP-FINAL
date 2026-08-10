<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * movimientos_inventario.estado, segun chk_mov_inv_estado.
 *
 * Un movimiento jamas se borra ni se edita: se marca `cancelado` y se genera el
 * contrario. Solo los `aplicado` suman en v_existencias.
 */
enum EstadoMovimiento: string implements EstadoPresentable
{
    case Aplicado = 'aplicado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Aplicado => 'Aplicado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aplicado => 'aprobado',
            self::Cancelado => 'cancelada',
        };
    }
}
