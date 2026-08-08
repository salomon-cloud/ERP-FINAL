<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\EstadoPresentable;

/**
 * documentos_empleado.estado, segun chk_documentos_estado.
 *
 * `pendiente` es un documento que el expediente exige y el empleado todavia no
 * entrega; `vencido` es uno entregado al que se le paso la vigencia.
 */
enum EstadoDocumentoEmpleado: string implements EstadoPresentable
{
    case Vigente = 'vigente';
    case Vencido = 'vencido';
    case Pendiente = 'pendiente';

    public function label(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Vencido => 'Vencido',
            self::Pendiente => 'Pendiente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vigente => 'activo',
            self::Vencido => 'inactivo',
            self::Pendiente => 'pendiente',
        };
    }
}
