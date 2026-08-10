<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

/**
 * cobros.forma_pago, segun chk_cobros_forma_pago.
 *
 * Son cinco y no tres como en los pagos a proveedor: a un cliente si se le
 * cobra con tarjeta o con liga de pago.
 */
enum FormaPagoCobro: string implements Etiquetable
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Cheque = 'cheque';
    case Tarjeta = 'tarjeta';
    case LigaPago = 'liga_pago';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Cheque => 'Cheque',
            self::Tarjeta => 'Tarjeta',
            self::LigaPago => 'Liga de pago',
        };
    }
}
