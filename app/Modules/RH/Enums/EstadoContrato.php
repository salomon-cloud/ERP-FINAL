<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * contratos.estado, segun chk_contratos_estado.
 *
 * `vencido` y `terminado` no son lo mismo: vencido es que se paso la fecha_fin
 * sin renovar (un pendiente que hay que atender, por eso se pinta en rojo);
 * terminado es un cierre deliberado y correcto.
 */
enum EstadoContrato: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Vigente = 'vigente';
    case Vencido = 'vencido';
    case Terminado = 'terminado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Vigente => 'Vigente',
            self::Vencido => 'Vencido',
            self::Terminado => 'Terminado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Vigente => 'activo',
            self::Vencido => 'inactivo',
            self::Terminado => 'permiso',
        };
    }
}
