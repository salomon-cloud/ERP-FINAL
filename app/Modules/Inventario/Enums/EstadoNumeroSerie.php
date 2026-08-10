<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * numeros_serie.estado, segun chk_series_estado.
 *
 * El ciclo de vida de una pieza identificada una por una: entra al almacen,
 * se vende, y de ahi puede volver por garantia o por devolucion.
 */
enum EstadoNumeroSerie: string implements EstadoPresentable
{
    case EnStock = 'en_stock';
    case Vendido = 'vendido';
    case Garantia = 'garantia';
    case Devuelto = 'devuelto';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::EnStock => 'En existencia',
            self::Vendido => 'Vendido',
            self::Garantia => 'En garantia',
            self::Devuelto => 'Devuelto',
            self::Baja => 'Dado de baja',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnStock => 'activo',
            self::Vendido => 'aprobado',
            self::Garantia, self::Devuelto => 'pendiente',
            self::Baja => 'cancelada',
        };
    }
}
