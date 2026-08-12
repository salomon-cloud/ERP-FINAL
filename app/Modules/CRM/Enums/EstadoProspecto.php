<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

enum EstadoProspecto: string implements EstadoPresentable
{
    case Nuevo = 'nuevo';
    case Contactado = 'contactado';
    case Calificado = 'calificado';
    case Convertido = 'convertido';
    case Perdido = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::Contactado => 'Contactado',
            self::Calificado => 'Calificado',
            self::Convertido => 'Convertido',
            self::Perdido => 'Perdido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Nuevo => 'pendiente',
            self::Contactado => 'permiso',
            self::Calificado => 'aprobado',
            self::Convertido => 'pagada',
            self::Perdido => 'cancelada',
        };
    }
}