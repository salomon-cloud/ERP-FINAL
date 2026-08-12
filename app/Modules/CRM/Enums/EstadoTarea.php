<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

enum EstadoTarea: string implements EstadoPresentable
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'pendiente',
            self::EnProceso => 'permiso',
            self::Completada => 'pagada',
            self::Cancelada => 'cancelada',
        };
    }
}