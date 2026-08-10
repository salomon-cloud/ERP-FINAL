<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\Etiquetable;

/**
 * devoluciones_compra.motivo, segun chk_devoluciones_compra_motivo.
 *
 * Sirve para algo mas que llenar un campo: agrupado por motivo, dice cual
 * proveedor manda producto defectuoso y cual se equivoca de articulo.
 */
enum MotivoDevolucionCompra: string implements Etiquetable
{
    case Defectuoso = 'defectuoso';
    case Equivocado = 'equivocado';
    case Excedente = 'excedente';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Defectuoso => 'Producto defectuoso',
            self::Equivocado => 'Producto equivocado',
            self::Excedente => 'Se recibio de mas',
            self::Otro => 'Otro motivo',
        };
    }
}
