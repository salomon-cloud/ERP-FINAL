<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\Etiquetable;

/**
 * El tipo de contrato, compartido por empleados.tipo_contrato y
 * contratos.tipo_contrato: las dos columnas tienen el mismo CHECK
 * (chk_empleados_tipo_contrato y chk_contratos_tipo), asi que un solo enum
 * garantiza que nunca se separen.
 */
enum TipoContrato: string implements Etiquetable
{
    case Indefinido = 'indefinido';
    case Temporal = 'temporal';
    case Practicas = 'practicas';
    case Servicios = 'servicios';
    case MedioTiempo = 'medio_tiempo';

    public function label(): string
    {
        return match ($this) {
            self::Indefinido => 'Indefinido',
            self::Temporal => 'Temporal',
            self::Practicas => 'Practicas',
            self::Servicios => 'Servicios profesionales',
            self::MedioTiempo => 'Medio tiempo',
        };
    }
}
