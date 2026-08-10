<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

/**
 * notas_credito.motivo, segun chk_notas_credito_motivo.
 *
 * El motivo NO es decorativo: solo `devolucion` regresa mercancia al almacen.
 * Un descuento posterior o la correccion de un error acreditan dinero sin que
 * vuelva ni una caja, y meterlas al inventario inventaria existencia que nadie
 * tiene.
 */
enum MotivoNotaCredito: string implements Etiquetable
{
    case Devolucion = 'devolucion';
    case Descuento = 'descuento';
    case Error = 'error';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Devolucion => 'Devolucion de mercancia',
            self::Descuento => 'Descuento posterior',
            self::Error => 'Correccion de un error',
            self::Otro => 'Otro motivo',
        };
    }

    /** La unica que mueve inventario. */
    public function regresaMercancia(): bool
    {
        return $this === self::Devolucion;
    }
}
