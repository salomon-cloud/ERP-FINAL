<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Models\User;
use App\Modules\Compras\Enums\EstadoRecepcion;
use App\Modules\Compras\Models\OrdenCompraLinea;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\Compras\Models\RecepcionLinea;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * EL FLUJO CRITICO DE COMPRAS: aplicar una recepcion.
 *
 * Es el punto donde una promesa se vuelve inventario. Aplicar hace cuatro cosas
 * en UNA transaccion, porque a medias dejarian el almacen mintiendo:
 *
 *   1. un movimiento `compra` (+) por linea, al costo de la orden
 *   2. sube `cantidad_recibida` en la linea de la orden
 *   3. recalcula el estado de la orden (recibida_parcial / recibida)
 *   4. cierra la requisicion si ya llego todo lo que pidio
 *
 * NO SE RECIBE DE MAS. La tolerancia sale de
 * `sisen.inventory.over_receipt_tolerance` y por omision es CERO: recibir mas
 * de lo pedido es una diferencia que alguien tiene que autorizar, no algo que
 * el sistema deba tragarse en silencio.
 */
class ServicioRecepcion
{
    public function __construct(
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly ServicioOrdenCompra $ordenes,
        private readonly ServicioRequisicion $requisiciones,
    ) {}

    /**
     * Agrega un renglon a recibir, contra una linea de la orden.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si la recepcion no es editable o se recibe de mas
     */
    public function agregarLinea(Recepcion $recepcion, array $datos): RecepcionLinea
    {
        if (! $recepcion->estado->esEditable()) {
            throw new RuntimeException(
                "La recepcion {$recepcion->numero_recepcion} esta {$recepcion->estado->label()} y ya no admite cambios."
            );
        }

        $ordenLinea = OrdenCompraLinea::findOrFail($datos['orden_compra_linea_id']);

        if ((int) $ordenLinea->orden_compra_id !== (int) $recepcion->orden_compra_id) {
            throw new RuntimeException('Esa linea pertenece a otra orden de compra.');
        }

        // Lo que ya trae esta recepcion de ese renglon, mas lo que se agrega
        // ahora: capturar dos parciales del mismo producto tampoco debe pasarse.
        $enEstaRecepcion = (float) RecepcionLinea::query()
            ->where('recepcion_id', $recepcion->id)
            ->where('orden_compra_linea_id', $ordenLinea->id)
            ->sum('cantidad_recibida');

        $this->verificarCabe($ordenLinea, $enEstaRecepcion + (float) $datos['cantidad_recibida']);

        return $recepcion->lineas()->create([
            'orden_compra_linea_id' => $ordenLinea->id,
            'producto_id' => $ordenLinea->producto_id,
            'ubicacion_id' => $datos['ubicacion_id'] ?? null,
            'cantidad_recibida' => $datos['cantidad_recibida'],
            // El costo viene de la orden, no de lo que alguien teclee: lo que
            // entra al inventario vale lo que se acordo pagar.
            'costo_unitario' => $ordenLinea->costo_unitario,
            'lote_id' => $datos['lote_id'] ?? null,
            'numero_serie_id' => $datos['numero_serie_id'] ?? null,
        ]);
    }

    /** @throws RuntimeException si la recepcion ya no es editable */
    public function eliminarLinea(Recepcion $recepcion, RecepcionLinea $linea): void
    {
        if (! $recepcion->estado->esEditable()) {
            throw new RuntimeException("La recepcion {$recepcion->numero_recepcion} ya no admite cambios.");
        }

        $linea->delete();
    }

    /**
     * Aplica la recepcion: la mercancia entra al inventario.
     *
     * @throws RuntimeException si la recepcion o su orden no estan en el estado correcto
     */
    public function aplicar(Recepcion $recepcion, User $usuario): Recepcion
    {
        if (! $recepcion->estado->esEditable()) {
            throw new RuntimeException(
                "La recepcion {$recepcion->numero_recepcion} esta {$recepcion->estado->label()} y ya se aplico."
            );
        }

        $recepcion->load(['lineas.ordenCompraLinea', 'ordenCompra']);

        if ($recepcion->lineas->isEmpty()) {
            throw new RuntimeException('Una recepcion sin lineas no se puede aplicar.');
        }

        $orden = $recepcion->ordenCompra;

        if (! $orden->estado->admiteRecepcion()) {
            throw new RuntimeException(
                "La orden {$orden->numero_orden} esta {$orden->estado->label()}: ".
                'solo se recibe contra una orden confirmada.'
            );
        }

        // Se vuelve a validar al aplicar, no solo al capturar: entre una cosa y
        // la otra pudo aplicarse OTRA recepcion de la misma orden y lo que
        // cabia ya no cabe.
        //
        // Se agrupa por renglon de la orden y se suma UNA vez: validar linea por
        // linea contaria dos veces lo que la recepcion ya tiene guardado.
        foreach ($recepcion->lineas->groupBy('orden_compra_linea_id') as $lineas) {
            $this->verificarCabe(
                $lineas->first()->ordenCompraLinea,
                (float) $lineas->sum('cantidad_recibida'),
            );
        }

        return DB::transaction(function () use ($recepcion, $usuario, $orden): Recepcion {
            foreach ($recepcion->lineas as $linea) {
                $this->movimientos->registrar(
                    TipoMovimiento::Compra,
                    (int) $linea->producto_id,
                    (int) $recepcion->almacen_id,
                    (float) $linea->cantidad_recibida,
                    (float) $linea->costo_unitario,
                    $recepcion,
                    [
                        'ubicacion_id' => $linea->ubicacion_id,
                        'lote_id' => $linea->lote_id,
                        'numero_serie_id' => $linea->numero_serie_id,
                        'organizacion_id' => $recepcion->organizacion_id,
                    ],
                );

                // increment() y no una asignacion: dos recepciones simultaneas
                // de la misma orden se suman en la base en vez de pisarse.
                $linea->ordenCompraLinea->increment('cantidad_recibida', (float) $linea->cantidad_recibida);
            }

            $recepcion->estado = EstadoRecepcion::Aplicada;
            $recepcion->recibido_por = $recepcion->recibido_por ?? $usuario->id;
            $recepcion->save();

            $recepcion->registrarBitacora('aplicada', [], [
                'estado' => EstadoRecepcion::Aplicada->value,
                'lineas' => $recepcion->lineas->count(),
            ]);

            $this->ordenes->actualizarEstadoPorRecepciones($orden);
            $this->requisiciones->cerrarSiSuOrdenEstaCompleta($orden->refresh());

            return $recepcion->refresh();
        });
    }

    /**
     * Cancela la recepcion.
     *
     * Si ya estaba aplicada, NO se borran sus movimientos: se les genera el
     * contrario. La mercancia estuvo en el almacen y el kardex tiene que poder
     * contarlo, aunque despues se corrigiera.
     *
     * @throws RuntimeException
     */
    public function cancelar(Recepcion $recepcion): Recepcion
    {
        if (! $recepcion->estado->esCancelable()) {
            throw new RuntimeException("La recepcion {$recepcion->numero_recepcion} ya esta cancelada.");
        }

        return DB::transaction(function () use ($recepcion): Recepcion {
            $estabaAplicada = $recepcion->estado === EstadoRecepcion::Aplicada;

            if ($estabaAplicada) {
                $recepcion->load('lineas.ordenCompraLinea');

                $this->movimientos->revertirDocumento($recepcion);

                foreach ($recepcion->lineas as $linea) {
                    $linea->ordenCompraLinea->decrement('cantidad_recibida', (float) $linea->cantidad_recibida);
                }
            }

            $recepcion->estado = EstadoRecepcion::Cancelada;
            $recepcion->save();

            $recepcion->registrarBitacora('cancelada', [], [
                'estado' => EstadoRecepcion::Cancelada->value,
                'movimientos_revertidos' => $estabaAplicada,
            ]);

            if ($estabaAplicada) {
                $this->ordenes->actualizarEstadoPorRecepciones($recepcion->ordenCompra);
            }

            return $recepcion->refresh();
        });
    }

    /**
     * La regla de oro de la recepcion: no se recibe mas de lo pedido.
     *
     * @param  float  $cantidadDeEstaRecepcion  el TOTAL que este documento va a
     *                                          meter de ese renglon, no un
     *                                          incremento
     *
     * @throws RuntimeException
     */
    private function verificarCabe(OrdenCompraLinea $ordenLinea, float $cantidadDeEstaRecepcion): void
    {
        $tolerancia = (float) config('sisen.inventory.over_receipt_tolerance', 0);

        $pedida = (float) $ordenLinea->cantidad;
        $yaRecibida = (float) $ordenLinea->cantidad_recibida;
        $maximo = $pedida * (1 + $tolerancia / 100);

        if ($yaRecibida + $cantidadDeEstaRecepcion <= $maximo + 0.000001) {
            return;
        }

        throw new RuntimeException(sprintf(
            'No se puede recibir mas de lo pedido: la orden pide %s de %s y ya se recibieron %s. '.
            'Solo quedan %s pendientes.',
            $this->numero($pedida),
            $ordenLinea->producto?->nombre ?? 'ese producto',
            $this->numero($yaRecibida),
            $this->numero(max($maximo - $yaRecibida, 0)),
        ));
    }

    /** Una cantidad sin ceros de relleno, para los mensajes al usuario. */
    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 6, '.', ''), '0'), '.');
    }
}
