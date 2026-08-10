<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * El par activo|inactivo que comparten productos, clientes y proveedores.
 *
 * Es un solo enum y no tres identicos porque las tres columnas guardan los
 * mismos dos valores (chk_productos_estado, chk_clientes_estado,
 * chk_proveedores_estado) y significan lo mismo: el registro sigue en uso o
 * quedo archivado sin borrarse.
 */
enum EstadoActivacion: string implements EstadoPresentable
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Inactivo => 'Inactivo',
        };
    }

    public function color(): string
    {
        return $this->value;
    }
}
