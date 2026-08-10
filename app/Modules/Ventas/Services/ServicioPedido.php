<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Compartido\Support\CalculadoraLinea;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Services\ServicioApartarExistencia;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use App\Modules\Ventas\Enums\EstadoPedido;
use App\Modules\Ventas\Models\Pedido;
use App\Modules\Ventas\Models\PedidoLinea;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El corazon de Ventas: confirmar, surtir, facturar y cancelar un pedido.
 *
 * CONFIRMAR aparta la existencia. Entre la confirmacion y el surtido pasan
 * horas o dias; si en ese hueco la mercancia siguiera apareciendo libre, se
 * vende dos veces y una de las dos ventas no se puede cumplir.
 *
 * SURTIR hace DOS movimientos por linea y EN ESTE ORDEN:
 *
 *   1. `liberacion_apartado` (+) libera la reserva de lo que se va a surtir
 *   2. `venta` (-) saca la mercancia de verdad
 *
 * El orden importa y no es un detalle: si la salida fuera primero, se validaria
 * contra un disponible que todavia tiene descontada la propia reserva de este
 * pedido y el surtido fallaria por "existencia insuficiente" teniendo la
 * mercancia apartada justo para el.
 */
class ServicioPedido
{
    public function __construct(
        private readonly ServicioApartarExistencia $apartados,
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly ServicioExistencias $existencias,
        private readonly ServicioResolverPrecio $precios,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si el pedido ya no es editable
     */
    public function agregarLinea(Pedido $pedido, array $datos): PedidoLinea
    {
        if (! $pedido->estado->esEditable()) {
            throw new RuntimeException(
                "El pedido {$pedido->numero_pedido} esta {$pedido->estado->label()} y ya no admite cambios en sus lineas."
            );
        }

        return DB::transaction(function () use ($pedido, $datos): PedidoLinea {
            $producto = Producto::findOrFail($datos['producto_id']);
            $cantidad = (float) $datos['cantidad'];

            $precio = filled($datos['precio_unitario'] ?? null)
                ? (float) $datos['precio_unitario']
                : $this->precios->resolver($producto, $pedido->cliente, $cantidad, $pedido->listaPrecio);

            $impuestoId = $datos['impuesto_id'] ?? $producto->impuesto_id;
            $tasa = $this->tasaDelImpuesto($impuestoId);

            $totales = CalculadoraLinea::calcular(
                $cantidad,
                $precio,
                (float) ($datos['porcentaje_descuento'] ?? 0),
                (float) ($datos['monto_descuento'] ?? 0),
                $tasa,
            );

            $linea = $pedido->lineas()->create([
                'producto_id' => $producto->id,
                'almacen_id' => $datos['almacen_id'] ?? null,
                'descripcion' => $datos['descripcion'] ?? $producto->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'monto_descuento' => $totales['monto_descuento'],
                'impuesto_id' => $impuestoId,
                'tasa_impuesto' => $tasa,
                'monto_impuesto' => $totales['monto_impuesto'],
                'subtotal' => $totales['subtotal'],
                'total' => $totales['total'],
            ]);

            $pedido->recalcularTotales();

            return $linea;
        });
    }

    /** @throws RuntimeException si el pedido ya no es editable */
    public function eliminarLinea(Pedido $pedido, PedidoLinea $linea): void
    {
        if (! $pedido->estado->esEditable()) {
            throw new RuntimeException("El pedido {$pedido->numero_pedido} ya no admite cambios en sus lineas.");
        }

        DB::transaction(function () use ($pedido, $linea): void {
            $linea->delete();
            $pedido->recalcularTotales();
        });
    }

    /**
     * Confirma el pedido y APARTA la existencia de cada linea.
     *
     * Si falta existencia de algo, no se aparta nada: la transaccion completa se
     * deshace y el mensaje dice exactamente que producto falta y cuanto hay. Un
     * pedido confirmado a medias seria peor que uno no confirmado.
     *
     * @param  int  $versionFila  la version que el usuario tenia en pantalla
     *
     * @throws RuntimeException
     */
    public function confirmar(Pedido $pedido, int $versionFila): Pedido
    {
        if (! $pedido->estado->esEditable()) {
            throw new RuntimeException(
                "El pedido {$pedido->numero_pedido} esta {$pedido->estado->label()} y ya fue confirmado."
            );
        }

        $pedido->load('lineas.producto');

        if ($pedido->lineas->isEmpty()) {
            throw new RuntimeException('Un pedido sin lineas no se puede confirmar.');
        }

        $this->verificarQueTodasLasLineasTenganAlmacen($pedido);

        return DB::transaction(function () use ($pedido, $versionFila): Pedido {
            $afectadas = Pedido::query()
                ->whereKey($pedido->getKey())
                ->where('version_fila', $versionFila)
                ->update([
                    'estado' => EstadoPedido::Confirmado->value,
                    'version_fila' => $versionFila + 1,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                throw new RuntimeException(
                    'Alguien mas modifico este pedido mientras lo revisabas. Vuelve a cargarlo antes de confirmarlo.'
                );
            }

            foreach ($pedido->lineas as $linea) {
                // Los productos que no llevan inventario (servicios) no se
                // apartan: no hay nada que reservar.
                if (! $linea->producto?->es_inventariable) {
                    continue;
                }

                $this->apartados->apartar(
                    $pedido,
                    (int) $linea->producto_id,
                    (int) $linea->almacen_id,
                    (float) $linea->cantidad,
                    ['organizacion_id' => $pedido->organizacion_id],
                );
            }

            $pedido->refresh();
            $pedido->registrarBitacora('confirmado', [], ['estado' => EstadoPedido::Confirmado->value]);

            return $pedido;
        });
    }

    /**
     * Surte el pedido: libera lo apartado y saca la mercancia del almacen.
     *
     * @param  array<int, float>|null  $cantidades  linea_id => cantidad a surtir.
     *                                              Sin esto se surte todo lo pendiente.
     *
     * @throws RuntimeException
     */
    public function surtir(Pedido $pedido, ?array $cantidades = null): Pedido
    {
        if (! $pedido->estado->admiteSurtido()) {
            throw new RuntimeException(
                "Solo se surte un pedido confirmado; {$pedido->numero_pedido} esta {$pedido->estado->label()}."
            );
        }

        $pedido->load('lineas.producto');

        return DB::transaction(function () use ($pedido, $cantidades): Pedido {
            $algoSurtido = false;

            foreach ($pedido->lineas as $linea) {
                $porSurtir = $cantidades !== null
                    ? min((float) ($cantidades[$linea->id] ?? 0), $linea->cantidad_pendiente)
                    : $linea->cantidad_pendiente;

                if ($porSurtir <= 0.000001) {
                    continue;
                }

                if ($linea->producto?->es_inventariable) {
                    // 1. Primero liberar el apartado de lo que se va a surtir.
                    //    Ver el comentario de la clase: al reves, la validacion
                    //    de existencia chocaria con la reserva de este mismo
                    //    pedido.
                    $this->apartados->liberar(
                        $pedido,
                        (int) $linea->producto_id,
                        (int) $linea->almacen_id,
                        $porSurtir,
                        ['organizacion_id' => $pedido->organizacion_id],
                    );

                    // 2. Y ahora si, la salida real.
                    $this->movimientos->registrar(
                        TipoMovimiento::Venta,
                        (int) $linea->producto_id,
                        (int) $linea->almacen_id,
                        $porSurtir,
                        $this->existencias->costoPromedio((int) $linea->producto_id, (int) $linea->almacen_id),
                        $pedido,
                        ['organizacion_id' => $pedido->organizacion_id],
                    );
                }

                $linea->increment('cantidad_surtida', $porSurtir);
                $algoSurtido = true;
            }

            if (! $algoSurtido) {
                throw new RuntimeException('No quedaba nada por surtir en este pedido.');
            }

            $pedido->load('lineas');

            if ($pedido->estaCompletamenteSurtido()) {
                $pedido->estado = EstadoPedido::Surtido;
                $pedido->save();
            }

            $pedido->registrarBitacora('surtido', [], [
                'estado' => $pedido->estado->value,
                'completo' => $pedido->estaCompletamenteSurtido(),
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Recalcula el estado segun lo facturado. Lo llama ServicioFactura.
     *
     * Es una funcion de lo facturado, no una decision de nadie.
     */
    public function actualizarEstadoPorFacturas(Pedido $pedido): Pedido
    {
        if ($pedido->estado === EstadoPedido::Cancelado) {
            return $pedido;
        }

        $facturado = $pedido->total_facturado;
        $total = (float) $pedido->total;

        $nuevo = match (true) {
            $facturado + 0.01 >= $total && $total > 0 => EstadoPedido::Facturado,
            $facturado > 0 => EstadoPedido::FacturadoParcial,
            default => $pedido->estado,
        };

        if ($pedido->estado !== $nuevo) {
            $anterior = $pedido->estado;
            $pedido->estado = $nuevo;
            $pedido->save();
            $pedido->registrarBitacora('facturacion_actualizada',
                ['estado' => $anterior->value],
                ['estado' => $nuevo->value, 'facturado' => $facturado]);
        }

        return $pedido->refresh();
    }

    /**
     * Cancela el pedido y libera lo que tuviera apartado.
     *
     * Un pedido SURTIDO ya no se cancela: la mercancia salio del almacen y
     * deshacerlo es una nota de credito con devolucion, que es otro documento y
     * deja su propio rastro.
     *
     * @throws RuntimeException
     */
    public function cancelar(Pedido $pedido): Pedido
    {
        if (! $pedido->estado->esCancelable()) {
            throw new RuntimeException(
                "El pedido {$pedido->numero_pedido} esta {$pedido->estado->label()}: ".
                'la mercancia ya salio del almacen. Levanta una nota de credito con devolucion.'
            );
        }

        return DB::transaction(function () use ($pedido): Pedido {
            $liberadas = $this->apartados->liberarTodo($pedido);

            $pedido->estado = EstadoPedido::Cancelado;
            $pedido->save();

            $pedido->registrarBitacora('cancelado', [], [
                'estado' => EstadoPedido::Cancelado->value,
                'apartados_liberados' => $liberadas,
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Confirmar aparta existencia, y apartar necesita saber de que almacen.
     *
     * @throws RuntimeException
     */
    private function verificarQueTodasLasLineasTenganAlmacen(Pedido $pedido): void
    {
        $sinAlmacen = $pedido->lineas
            ->filter(fn (PedidoLinea $linea) => $linea->producto?->es_inventariable && $linea->almacen_id === null);

        if ($sinAlmacen->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Falta elegir el almacen de %d linea(s): %s. Sin almacen no se puede apartar la existencia.',
                $sinAlmacen->count(),
                $sinAlmacen->map(fn (PedidoLinea $l) => $l->producto?->nombre ?? 'sin producto')->implode(', '),
            ));
        }
    }

    /** La tasa del impuesto como fraccion (0.16 para el 16%). */
    private function tasaDelImpuesto(?int $impuestoId): float
    {
        if ($impuestoId === null) {
            return 0.0;
        }

        return (float) (DB::table('impuestos')->where('id', $impuestoId)->value('tasa') ?? 0);
    }
}
