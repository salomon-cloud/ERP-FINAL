<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

use App\Modules\Compartido\Contracts\EstadoPresentable;

/**
 * movimientos_inventario.tipo_movimiento, segun chk_mov_inv_tipo.
 *
 * Cada caso dice ademas en que sentido mueve la existencia, porque la columna
 * `cantidad` va con signo y quien crea el movimiento no debe tener que
 * acordarse de cual le toca a cada tipo: se lo pregunta al enum.
 *
 * LOS DOS CASOS RAROS: `apartado` y `liberacion_apartado`.
 *
 * No son existencia fisica, son una reserva. Un pedido confirmado aparta la
 * mercancia con un movimiento negativo, y al surtirla se libera con uno
 * positivo mientras la salida real (`venta`) descuenta la existencia fisica.
 * Los dos se graban con costo_unitario = 0 a proposito, para que v_existencias
 * siga valorizando bien: ahi `existencia` queda como la existencia DISPONIBLE
 * (fisica menos apartada) y `valor_inventario` como el valor de la fisica.
 * El desglose de las tres cifras lo da ServicioExistencias.
 */
enum TipoMovimiento: string implements EstadoPresentable
{
    case Compra = 'compra';
    case Venta = 'venta';
    case TraspasoEntrada = 'traspaso_entrada';
    case TraspasoSalida = 'traspaso_salida';
    case AjusteEntrada = 'ajuste_entrada';
    case AjusteSalida = 'ajuste_salida';
    case DevolucionEntrada = 'devolucion_entrada';
    case DevolucionSalida = 'devolucion_salida';
    case Conteo = 'conteo';
    case Inicial = 'inicial';
    case Apartado = 'apartado';
    case LiberacionApartado = 'liberacion_apartado';

    public function label(): string
    {
        return match ($this) {
            self::Compra => 'Entrada por compra',
            self::Venta => 'Salida por venta',
            self::TraspasoEntrada => 'Entrada por traspaso',
            self::TraspasoSalida => 'Salida por traspaso',
            self::AjusteEntrada => 'Ajuste positivo',
            self::AjusteSalida => 'Ajuste negativo',
            self::DevolucionEntrada => 'Devolucion de cliente',
            self::DevolucionSalida => 'Devolucion a proveedor',
            self::Conteo => 'Conteo fisico',
            self::Inicial => 'Carga inicial',
            self::Apartado => 'Apartado',
            self::LiberacionApartado => 'Liberacion de apartado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Compra, self::TraspasoEntrada, self::AjusteEntrada,
            self::DevolucionEntrada, self::Inicial, self::LiberacionApartado => 'activo',
            self::Venta, self::TraspasoSalida, self::AjusteSalida,
            self::DevolucionSalida, self::Apartado => 'cancelada',
            self::Conteo => 'pendiente',
        };
    }

    /**
     * El signo que le corresponde a la cantidad: +1 suma existencia, -1 la
     * resta. `conteo` no tiene signo fijo -- la diferencia de un conteo puede
     * ser de cualquier lado -- y por eso devuelve 0: quien lo crea manda el
     * signo ya puesto en la cantidad.
     */
    public function signo(): int
    {
        return match ($this) {
            self::Compra, self::TraspasoEntrada, self::AjusteEntrada,
            self::DevolucionEntrada, self::Inicial, self::LiberacionApartado => 1,
            self::Venta, self::TraspasoSalida, self::AjusteSalida,
            self::DevolucionSalida, self::Apartado => -1,
            self::Conteo => 0,
        };
    }

    /** Reserva, no existencia fisica: no entra en el valor del inventario. */
    public function esApartado(): bool
    {
        return $this === self::Apartado || $this === self::LiberacionApartado;
    }

    /** Los tipos que mueven existencia fisica de verdad. */
    public function esFisico(): bool
    {
        return ! $this->esApartado();
    }

    /**
     * El tipo con que se deshace este movimiento.
     *
     * Cancelar un documento ya aplicado no borra su rastro: genera el
     * movimiento contrario, y el par queda en el kardex contando la historia
     * completa (docs/david.md §7.2).
     *
     * Una compra se deshace como devolucion al proveedor y una venta como
     * devolucion del cliente, porque eso es lo que de verdad pasa. La carga
     * inicial no tiene contrario natural y se corrige con un ajuste.
     */
    public function inverso(): self
    {
        return match ($this) {
            self::Compra => self::DevolucionSalida,
            self::Venta => self::DevolucionEntrada,
            self::TraspasoEntrada => self::TraspasoSalida,
            self::TraspasoSalida => self::TraspasoEntrada,
            self::AjusteEntrada => self::AjusteSalida,
            self::AjusteSalida => self::AjusteEntrada,
            self::DevolucionEntrada => self::DevolucionSalida,
            self::DevolucionSalida => self::DevolucionEntrada,
            self::Apartado => self::LiberacionApartado,
            self::LiberacionApartado => self::Apartado,
            self::Conteo => self::Conteo,
            self::Inicial => self::AjusteSalida,
        };
    }
}
