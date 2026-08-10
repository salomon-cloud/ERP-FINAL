<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoMovimiento;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\MovimientoInventario;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * EL UNICO QUE ESCRIBE EN EL LIBRO DE MOVIMIENTOS.
 *
 * Ventas, Compras y el propio Inventario le piden entradas y salidas a este
 * servicio; ninguno hace `MovimientoInventario::create()` por su cuenta. Es lo
 * que garantiza que toda existencia que se mueve pase por la misma validacion,
 * el mismo signo, la misma transaccion y la misma auditoria.
 *
 * QUIEN LLAMA A QUIEN (docs/david.md §7.3)
 *
 *   ServicioRecepcion (Compras)      -> compra (+)
 *   ServicioPedido (Ventas)          -> apartado (-), liberacion_apartado (+), venta (-)
 *   ServicioNotaCredito (Ventas)     -> devolucion_entrada (+)
 *   ServicioDevolucionCompra         -> devolucion_salida (-)
 *   ServicioTraspaso                 -> traspaso_salida (-) / traspaso_entrada (+)
 *   ServicioAjusteInventario         -> ajuste_entrada / ajuste_salida
 *   ServicioConteo                   -> conteo (con el signo de la diferencia)
 */
class ServicioMovimientoInventario
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    /**
     * Registra un movimiento.
     *
     * `$cantidad` viaja SIEMPRE en positivo y en unidad base: el signo lo pone
     * el tipo. La unica excepcion es `conteo`, cuyo signo depende de si sobro o
     * falto y por eso se acepta tal cual viene.
     *
     * @param  array<string, mixed>  $extra  ubicacion_id, lote_id, numero_serie_id, organizacion_id
     *
     * @throws RuntimeException si la salida dejaria la existencia en negativo
     */
    public function registrar(
        TipoMovimiento $tipo,
        int $productoId,
        int $almacenId,
        float $cantidad,
        float $costoUnitario = 0,
        ?Model $origen = null,
        array $extra = [],
    ): MovimientoInventario {
        $signo = $tipo->signo();

        // El conteo trae su propio signo; los demas lo reciben del tipo, de modo
        // que quien llama nunca tenga que acordarse de poner el menos.
        $cantidadConSigno = $signo === 0 ? $cantidad : abs($cantidad) * $signo;

        if (abs($cantidadConSigno) < 0.000001) {
            throw new RuntimeException('Un movimiento de inventario no puede ser de cantidad cero.');
        }

        return DB::transaction(function () use (
            $tipo, $productoId, $almacenId, $cantidadConSigno, $costoUnitario, $origen, $extra
        ): MovimientoInventario {
            // Serializa a los que tocan el mismo producto. Sin esto, dos
            // pedidos simultaneos pueden leer la misma existencia y apartarla
            // los dos: se sobrevende sin que ninguna validacion se entere.
            Producto::query()->whereKey($productoId)->lockForUpdate()->first();

            if ($cantidadConSigno < 0) {
                $this->verificarDisponible($productoId, $almacenId, abs($cantidadConSigno), $tipo);
            }

            $movimiento = new MovimientoInventario([
                'organizacion_id' => $extra['organizacion_id'] ?? null,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
                'ubicacion_id' => $extra['ubicacion_id'] ?? null,
                'tipo_movimiento' => $tipo,
                'cantidad' => $cantidadConSigno,
                // Un apartado no es valor: es una promesa. Grabarlo en cero es
                // lo que deja intacto el valor_inventario de v_existencias.
                'costo_unitario' => $tipo->esApartado() ? 0 : abs($costoUnitario),
                'origen_tipo' => $origen?->getTable() ?? ($extra['origen_tipo'] ?? null),
                'origen_id' => $origen?->getKey() ?? ($extra['origen_id'] ?? null),
                'lote_id' => $extra['lote_id'] ?? null,
                'numero_serie_id' => $extra['numero_serie_id'] ?? null,
            ]);

            $movimiento->estado = EstadoMovimiento::Aplicado;
            $movimiento->aplicado_en = now();
            $movimiento->aplicado_por = Auth::id();
            $movimiento->save();

            return $movimiento;
        });
    }

    /**
     * Deshace un movimiento generando el contrario.
     *
     * No se borra ni se edita el original: el kardex tiene que poder contar que
     * paso y cuando se corrigio. El par queda visible con el mismo documento de
     * origen.
     */
    public function revertir(MovimientoInventario $movimiento): MovimientoInventario
    {
        $inverso = $movimiento->tipo_movimiento->inverso();
        $cantidad = (float) $movimiento->cantidad;

        return $this->registrar(
            $inverso,
            (int) $movimiento->producto_id,
            (int) $movimiento->almacen_id,
            // El conteo no tiene signo propio: se invierte a mano.
            $inverso->signo() === 0 ? -$cantidad : abs($cantidad),
            (float) $movimiento->costo_unitario,
            null,
            [
                'ubicacion_id' => $movimiento->ubicacion_id,
                'lote_id' => $movimiento->lote_id,
                'numero_serie_id' => $movimiento->numero_serie_id,
                'organizacion_id' => $movimiento->organizacion_id,
                // El contrario cuelga del MISMO documento que el original, para
                // que el kardex del papel muestre el movimiento y su correccion
                // uno junto al otro.
                'origen_tipo' => $movimiento->origen_tipo,
                'origen_id' => $movimiento->origen_id,
            ],
        );
    }

    /**
     * Deshace TODOS los movimientos aplicados de un documento.
     *
     * Es lo que hace cancelar una recepcion ya aplicada o una nota de credito
     * emitida: cada movimiento suyo recibe su contrario.
     *
     * @return int cuantos movimientos se revirtieron
     */
    public function revertirDocumento(Model $documento): int
    {
        $movimientos = MovimientoInventario::query()
            ->delOrigen($documento->getTable(), (int) $documento->getKey())
            ->aplicados()
            ->get();

        foreach ($movimientos as $movimiento) {
            $this->revertir($movimiento);
        }

        return $movimientos->count();
    }

    /**
     * La regla que sostiene todo el modulo: el libro de un
     * (producto, almacen) no puede quedar negativo.
     *
     * Se valida contra la suma completa -- que ya trae descontado lo apartado --
     * asi que la misma comprobacion impide vender sin existencia y apartar dos
     * veces la misma pieza.
     *
     * La validacion es por almacen y no por ubicacion a proposito: una salida
     * no siempre dice de que anaquel sale, y exigirlo obligaria a capturar
     * ubicacion en cada venta.
     *
     * @throws RuntimeException
     */
    private function verificarDisponible(int $productoId, int $almacenId, float $cantidad, TipoMovimiento $tipo): void
    {
        if (config('sisen.inventory.negative_stock_allowed', false) === true) {
            return;
        }

        $disponible = $this->existencias->disponible($productoId, $almacenId);

        if ($disponible + 0.000001 >= $cantidad) {
            return;
        }

        $producto = Producto::find($productoId);

        throw new RuntimeException(sprintf(
            'Existencia insuficiente para %s: se necesitan %s y solo hay %s disponibles (%s).',
            $producto?->etiqueta ?? "producto #{$productoId}",
            rtrim(rtrim(number_format($cantidad, 6, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($disponible, 6, '.', ''), '0'), '.'),
            $tipo->label(),
        ));
    }
}
