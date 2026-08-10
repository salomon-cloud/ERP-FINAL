<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\MovimientoInventario;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * La reserva de existencia: lo que Ventas consume al confirmar un pedido.
 *
 * QUE PROBLEMA RESUELVE. Entre que un pedido se confirma y que se surte pasan
 * horas o dias. Si en ese hueco la existencia sigue apareciendo libre, se vende
 * dos veces la misma pieza y una de las dos ventas no se puede cumplir.
 * Apartar la baja del disponible desde el momento de la confirmacion, sin
 * sacarla del anaquel: fisicamente sigue ahi, comprometida.
 *
 * Se implementa con los tipos `apartado` y `liberacion_apartado` del mismo
 * libro, y no con una tabla de reservas, porque asi la reserva hereda gratis la
 * trazabilidad, la auditoria y la validacion de existencia de cualquier otro
 * movimiento (docs/david.md §7.3).
 */
class ServicioApartarExistencia
{
    public function __construct(private readonly ServicioMovimientoInventario $movimientos) {}

    /**
     * Aparta una cantidad para un documento.
     *
     * @throws RuntimeException si no hay disponible suficiente
     */
    public function apartar(Model $documento, int $productoId, int $almacenId, float $cantidad, array $extra = []): MovimientoInventario
    {
        return $this->movimientos->registrar(
            TipoMovimiento::Apartado,
            $productoId,
            $almacenId,
            $cantidad,
            0,
            $documento,
            $extra,
        );
    }

    /**
     * Libera una cantidad apartada, sin pasarse de lo que este documento tiene
     * realmente comprometido.
     *
     * Devuelve null cuando no queda nada por liberar: pedir la liberacion de
     * mas de lo apartado es un error de quien llama, pero convertirlo en una
     * excepcion romperia cancelaciones legitimas de pedidos surtidos a medias.
     */
    public function liberar(Model $documento, int $productoId, int $almacenId, float $cantidad, array $extra = []): ?MovimientoInventario
    {
        $pendiente = min($cantidad, $this->apartadoDe($documento, $productoId, $almacenId));

        if ($pendiente <= 0.000001) {
            return null;
        }

        return $this->movimientos->registrar(
            TipoMovimiento::LiberacionApartado,
            $productoId,
            $almacenId,
            $pendiente,
            0,
            $documento,
            $extra,
        );
    }

    /** Libera todo lo que un documento tenga apartado. Cancelar un pedido es esto. */
    public function liberarTodo(Model $documento): int
    {
        $pendientes = MovimientoInventario::query()
            ->delOrigen($documento->getTable(), (int) $documento->getKey())
            ->aplicados()
            ->whereIn('tipo_movimiento', [
                TipoMovimiento::Apartado->value,
                TipoMovimiento::LiberacionApartado->value,
            ])
            ->selectRaw('producto_id, almacen_id, SUM(cantidad) as saldo')
            ->groupBy('producto_id', 'almacen_id')
            ->havingRaw('SUM(cantidad) < 0')
            ->get();

        foreach ($pendientes as $pendiente) {
            $this->movimientos->registrar(
                TipoMovimiento::LiberacionApartado,
                (int) $pendiente->producto_id,
                (int) $pendiente->almacen_id,
                abs((float) $pendiente->saldo),
                0,
                $documento,
            );
        }

        return $pendientes->count();
    }

    /**
     * Cuanto tiene apartado este documento de un producto en un almacen.
     *
     * Es la suma de sus apartados y liberaciones: el saldo, no el bruto.
     */
    public function apartadoDe(Model $documento, int $productoId, int $almacenId): float
    {
        $saldo = (float) MovimientoInventario::query()
            ->delOrigen($documento->getTable(), (int) $documento->getKey())
            ->aplicados()
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->whereIn('tipo_movimiento', [
                TipoMovimiento::Apartado->value,
                TipoMovimiento::LiberacionApartado->value,
            ])
            ->sum('cantidad');

        return max(-$saldo, 0.0);
    }
}
