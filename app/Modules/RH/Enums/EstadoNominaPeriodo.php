<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * nomina_periodos.estado, segun chk_nomina_periodo_estado.
 *
 * Ciclo: abierto -> procesado -> cerrado. Solo un periodo abierto admite
 * corridas nuevas; cerrarlo es lo que impide reprocesar una quincena ya pagada.
 */
enum EstadoNominaPeriodo: string implements EstadoPresentable
{
    case Abierto = 'abierto';
    case Procesado = 'procesado';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Procesado => 'Procesado',
            self::Cerrado => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Abierto => 'pendiente',
            self::Procesado => 'aprobado',
            self::Cerrado => 'permiso',
        };
    }

    /** Un periodo solo acepta corridas nuevas mientras esta abierto. */
    public function admiteCorridas(): bool
    {
        return $this === self::Abierto;
    }
}
