<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

enum EtapaOportunidad: string implements EstadoPresentable
{
    case Prospeccion = 'prospeccion';
    case Calificacion = 'calificacion';
    case Propuesta = 'propuesta';
    case Negociacion = 'negociacion';
    case Ganada = 'ganada';
    case Perdida = 'perdida';

    public function label(): string
    {
        return match ($this) {
            self::Prospeccion => 'Prospeccion',
            self::Calificacion => 'Calificacion',
            self::Propuesta => 'Propuesta',
            self::Negociacion => 'Negociacion',
            self::Ganada => 'Ganada',
            self::Perdida => 'Perdida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prospeccion => 'pendiente',
            self::Calificacion => 'permiso',
            self::Propuesta => 'aprobado',
            self::Negociacion => 'surtido',
            self::Ganada => 'pagada',
            self::Perdida => 'cancelada',
        };
    }
}