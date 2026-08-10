<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * conteos_inventario.estado, segun chk_conteos_estado.
 *
 *   borrador -> en_proceso -> contado -> ajustado -> cerrado
 *
 * `en_proceso` es el momento en que se fotografia la existencia esperada; a
 * partir de ahi la lista de productos del conteo ya no cambia, porque si
 * cambiara la diferencia dejaria de significar algo.
 */
enum EstadoConteo: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case EnProceso = 'en_proceso';
    case Contado = 'contado';
    case Ajustado = 'ajustado';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnProceso => 'En proceso',
            self::Contado => 'Contado',
            self::Ajustado => 'Ajustado',
            self::Cerrado => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::EnProceso, self::Contado => 'permiso',
            self::Ajustado, self::Cerrado => 'aprobado',
        };
    }

    /** Solo mientras se captura se pueden tocar las cantidades contadas. */
    public function admiteCaptura(): bool
    {
        return $this === self::EnProceso;
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }
}
