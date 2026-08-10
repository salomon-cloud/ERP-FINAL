<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * facturas_proveedor.estado, segun chk_facturas_proveedor_estado.
 *
 *   borrador -> contabilizada -> pagada_parcial -> pagada
 *       \_____________\_________________\________ cancelada
 *
 * `pagada_parcial` y `pagada` no las elige nadie a mano: las recalcula
 * ServicioPago comparando total_pagado contra total.
 */
enum EstadoFacturaProveedor: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Contabilizada = 'contabilizada';
    case PagadaParcial = 'pagada_parcial';
    case Pagada = 'pagada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Contabilizada => 'Contabilizada',
            self::PagadaParcial => 'Pagada parcial',
            self::Pagada => 'Pagada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Contabilizada, self::PagadaParcial => 'permiso',
            self::Pagada => 'pagada',
            self::Cancelada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Solo se paga lo que ya paso por contabilidad. */
    public function admitePago(): bool
    {
        return in_array($this, [self::Contabilizada, self::PagadaParcial], true);
    }

    public function esCancelable(): bool
    {
        return in_array($this, [self::Borrador, self::Contabilizada], true);
    }
}
