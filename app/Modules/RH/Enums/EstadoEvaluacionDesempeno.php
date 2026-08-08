<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * evaluaciones_desempeno.estado, segun chk_evaluaciones_estado.
 *
 * Ciclo: la escribe el evaluador (borrador), la manda (enviada) y el empleado
 * la da por vista (reconocida).
 */
enum EstadoEvaluacionDesempeno: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Reconocida = 'reconocida';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Reconocida => 'Reconocida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Enviada => 'permiso',
            self::Reconocida => 'aprobado',
        };
    }
}
