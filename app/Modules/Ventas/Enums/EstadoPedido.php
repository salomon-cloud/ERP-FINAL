<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * pedidos.estado, segun chk_pedidos_estado.
 *
 *   borrador -> confirmado -> surtido -> facturado_parcial -> facturado
 *       \___________\______ cancelado
 *
 * CONFIRMAR es lo que APARTA la existencia: a partir de ahi la mercancia esta
 * comprometida con este cliente aunque siga en el anaquel.
 *
 * SURTIDO ya no se cancela. La mercancia salio del almacen; deshacerlo es una
 * nota de credito con devolucion, que es otro documento y deja su propio rastro.
 */
enum EstadoPedido: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Confirmado = 'confirmado';
    case Surtido = 'surtido';
    case FacturadoParcial = 'facturado_parcial';
    case Facturado = 'facturado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Confirmado => 'Confirmado',
            self::Surtido => 'Surtido',
            self::FacturadoParcial => 'Facturado parcial',
            self::Facturado => 'Facturado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Confirmado => 'permiso',
            self::Surtido => 'presente',
            self::FacturadoParcial => 'permiso',
            self::Facturado => 'aprobado',
            self::Cancelado => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Solo un pedido confirmado se surte: antes no hay existencia apartada. */
    public function admiteSurtido(): bool
    {
        return $this === self::Confirmado;
    }

    /** Se factura lo que ya salio del almacen. */
    public function admiteFactura(): bool
    {
        return in_array($this, [self::Surtido, self::FacturadoParcial], true);
    }

    /**
     * Cancelable mientras nada haya salido del almacen.
     *
     * Un pedido confirmado si se cancela: se liberan sus apartados y la
     * existencia vuelve a estar disponible sin que nada se haya movido de sitio.
     */
    public function esCancelable(): bool
    {
        return in_array($this, [self::Borrador, self::Confirmado], true);
    }
}
