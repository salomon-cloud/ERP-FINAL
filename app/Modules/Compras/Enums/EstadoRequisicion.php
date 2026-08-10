<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * requisiciones.estado, segun chk_requisiciones_estado.
 *
 *   borrador -> enviada -> aprobada -> convertida -> cerrada
 *                   \____ rechazada
 *
 * Una requisicion es una PETICION interna, no un compromiso con nadie de fuera:
 * por eso se aprueba o se rechaza en vez de cancelarse, y por eso rechazarla no
 * deshace nada.
 */
enum EstadoRequisicion: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Convertida = 'convertida';
    case Cerrada = 'cerrada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
            self::Convertida => 'Convertida en orden',
            self::Cerrada => 'Cerrada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Enviada => 'permiso',
            self::Aprobada, self::Convertida, self::Cerrada => 'aprobado',
            self::Rechazada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Solo lo aprobado se convierte en orden de compra. */
    public function esConvertible(): bool
    {
        return $this === self::Aprobada;
    }

    public function esRevisable(): bool
    {
        return $this === self::Enviada;
    }
}
