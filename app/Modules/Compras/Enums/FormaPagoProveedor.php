<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

/**
 * pagos.forma_pago, segun chk_pagos_forma.
 *
 * Son tres y no cinco como en los cobros: a un proveedor no se le paga con
 * tarjeta ni con liga de pago, y el CHECK de la tabla lo refleja.
 */
enum FormaPagoProveedor: string implements Etiquetable
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Cheque => 'Cheque',
        };
    }
}
