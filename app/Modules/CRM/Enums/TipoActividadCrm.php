<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

enum TipoActividadCrm: string implements Etiquetable
{
    case Llamada = 'llamada';
    case Correo = 'correo';
    case Reunion = 'reunion';
    case Nota = 'nota';
    case Tarea = 'tarea';

    public function label(): string
    {
        return match ($this) {
            self::Llamada => 'Llamada',
            self::Correo => 'Correo',
            self::Reunion => 'Reunion',
            self::Nota => 'Nota',
            self::Tarea => 'Tarea',
        };
    }
}