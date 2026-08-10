<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * pagos.estado, segun chk_pagos_estado.
 *
 * Un pago aplicado ya salio del banco: no se edita. Corregirlo es cancelarlo,
 * lo que devuelve su importe al saldo de la factura y pide la poliza de reversa.
 */
enum EstadoPago: string implements EstadoPresentable
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
            self::Aplicado => 'pagada',
            self::Cancelado => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }
}
