<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * ajustes_inventario.estado, segun chk_ajustes_estado.
 *
 *   borrador -> aplicado
 *       \______ cancelado
 *
 * Aplicar exige el privilegio `inventario.ajustes.aprobar`: un ajuste es la
 * unica forma de cambiar existencia sin un documento comercial detras, y por
 * eso no la firma cualquiera.
 */
enum EstadoAjuste: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Aplicado = 'aplicado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Aplicado => 'Aplicado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Aplicado => 'aprobado',
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
