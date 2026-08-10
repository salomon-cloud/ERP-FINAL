<?php

declare(strict_types=1);

namespace App\Modules\Compras\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * ordenes_compra.estado, segun chk_ordenes_compra_estado.
 *
 *   borrador -> enviada -> confirmada -> recibida_parcial -> recibida -> facturada
 *       \___________\___________\______ cancelada (solo si no se ha recibido nada)
 *
 * `confirmada` es la autorizacion interna; `enviada` es que el proveedor ya la
 * tiene. El esquema no separa una "aprobada" como en requisiciones, asi que
 * confirmar cumple ese papel.
 *
 * Cancelar una orden con recepciones NO es posible: la mercancia ya entro al
 * almacen y borrar el papel dejaria existencia sin respaldo.
 */
enum EstadoOrdenCompra: string implements EstadoPresentable
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Confirmada = 'confirmada';
    case RecibidaParcial = 'recibida_parcial';
    case Recibida = 'recibida';
    case Facturada = 'facturada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada al proveedor',
            self::Confirmada => 'Confirmada',
            self::RecibidaParcial => 'Recibida parcial',
            self::Recibida => 'Recibida',
            self::Facturada => 'Facturada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'pendiente',
            self::Enviada, self::Confirmada, self::RecibidaParcial => 'permiso',
            self::Recibida, self::Facturada => 'aprobado',
            self::Cancelada => 'cancelada',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }

    /** Se cancela mientras no haya entrado nada al almacen. */
    public function esCancelable(): bool
    {
        return in_array($this, [self::Borrador, self::Enviada, self::Confirmada], true);
    }

    /** Solo una orden confirmada o a medio recibir admite recepciones. */
    public function admiteRecepcion(): bool
    {
        return in_array($this, [self::Confirmada, self::RecibidaParcial], true);
    }

    /** Se factura lo que ya llego, completo o parcial. */
    public function admiteFactura(): bool
    {
        return in_array($this, [self::RecibidaParcial, self::Recibida], true);
    }
}
