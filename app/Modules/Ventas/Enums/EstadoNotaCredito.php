<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/** notas_credito.estado, segun chk_notas_credito_estado. */
enum EstadoNotaCredito: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Emitida = 'emitida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Emitida => 'Emitida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Emitida => 'aprobado',
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
