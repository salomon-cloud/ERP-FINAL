<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * recepciones.estado, segun chk_recepciones_estado.
 *
 *   borrador -> aplicada
 *       \______ cancelada
 *
 * Aplicar es el momento exacto en que la mercancia entra al inventario. Una
 * recepcion aplicada se puede cancelar, pero eso no borra sus movimientos: les
 * genera el contrario, porque la mercancia SI estuvo en el almacen.
 */
enum EstadoRecepcion: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Aplicada = 'aplicada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Aplicada => 'Aplicada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Aplicada => 'aprobado',
            self::Cancelada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    public function esCancelable(): bool
    {
        return $this !== self::Cancelada;
    }
}
