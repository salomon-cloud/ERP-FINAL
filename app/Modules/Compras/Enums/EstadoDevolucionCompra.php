<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/** devoluciones_compra.estado, segun chk_devoluciones_compra_estado. */
enum EstadoDevolucionCompra: string implements EstadoPresentable
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
