<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * cotizaciones.estado, segun chk_cotizaciones_estado.
 *
 *   borrador -> enviada -> aceptada -> convertida
 *                   \____ rechazada
 *   borrador/enviada -> cancelada
 *
 * "Vencida" NO es un estado guardado: se DERIVA de comparar `vigencia` con hoy
 * (ver estaVencida() en el modelo). Guardarla obligaria a un cron que
 * recorriera todas las cotizaciones cada noche solo para que una fecha
 * pasara sola.
 */
enum EstadoCotizacion: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Aceptada = 'aceptada';
    case Rechazada = 'rechazada';
    case Convertida = 'convertida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada al cliente',
            self::Aceptada => 'Aceptada',
            self::Rechazada => 'Rechazada',
            self::Convertida => 'Convertida en pedido',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Enviada => 'permiso',
            self::Aceptada, self::Convertida => 'aprobado',
            self::Rechazada, self::Cancelada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Solo una cotizacion aceptada se vuelve pedido. */
    public function esConvertible(): bool
    {
        return $this === self::Aceptada;
    }

    /** El cliente solo responde a lo que ya recibio. */
    public function admiteRespuesta(): bool
    {
        return $this === self::Enviada;
    }

    public function esCancelable(): bool
    {
        return in_array($this, [self::Borrador, self::Enviada, self::Aceptada], true);
    }
}
