<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

enum OrigenProspecto: string implements Etiquetable
{
    case Web = 'web';
    case Referido = 'referido';
    case Llamada = 'llamada';
    case Evento = 'evento';
    case Feria = 'feria';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Referido => 'Referido',
            self::Llamada => 'Llamada',
            self::Evento => 'Evento',
            self::Feria => 'Feria',
            self::Otro => 'Otro',
        };
    }
}