<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * facturas.estado, segun chk_facturas_estado.
 *
 *   borrador -> emitida -> cobrada_parcial -> cobrada
 *       \____________\____________\_________ cancelada
 *
 * SOBRE 'vencida': el CHECK de la tabla lo permite, pero este sistema NO lo
 * guarda. Vencida se DERIVA de la fecha de vencimiento y del saldo (ver
 * Factura::estaVencida), porque un estado guardado que depende de la fecha de
 * hoy exige un cron nocturno que lo refresque y se desincroniza en cuanto ese
 * cron falla una noche.
 */
enum EstadoFactura: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Emitida = 'emitida';
    case CobradaParcial = 'cobrada_parcial';
    case Cobrada = 'cobrada';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Emitida => 'Emitida',
            self::CobradaParcial => 'Cobrada parcial',
            self::Cobrada => 'Cobrada',
            self::Vencida => 'Vencida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Emitida, self::CobradaParcial => 'permiso',
            self::Cobrada => 'pagada',
            self::Vencida => 'retardo',
            self::Cancelada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Solo se cobra lo que ya se emitio. */
    public function admiteCobro(): bool
    {
        return in_array($this, [self::Emitida, self::CobradaParcial, self::Vencida], true);
    }

    /** Una nota de credito se hace contra una factura ya emitida. */
    public function admiteNotaCredito(): bool
    {
        return in_array($this, [self::Emitida, self::CobradaParcial, self::Cobrada, self::Vencida], true);
    }

    public function esCancelable(): bool
    {
        return $this !== self::Cancelada;
    }
}
