<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Models\User;
use App\Modules\Inventario\Enums\EstadoConteo;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\ConteoInventario;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El inventario fisico, en tres actos:
 *
 *   iniciar   fotografia lo que el sistema cree tener (cantidad_esperada)
 *   capturar  el almacenista teclea lo que conto     (cantidad_contada)
 *   cerrar    la diferencia se vuelve movimientos     (tipo `conteo`)
 *
 * La foto se toma al INICIAR y no al cerrar. Si se recalculara al cerrar, la
 * existencia esperada se moveria con cada venta que ocurra mientras la gente
 * cuenta, y la diferencia dejaria de significar algo.
 */
class ServicioConteo
{
    public function __construct(
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly ServicioExistencias $existencias,
    ) {}

    /**
     * Congela la lista de productos a contar con su existencia esperada.
     *
     * Se cargan los productos con movimiento en el almacen; si el conteo apunta
     * a una ubicacion concreta, solo los de esa ubicacion. Un producto que no
     * deberia estar ahi y aparece se agrega a mano durante la captura.
     *
     * @throws RuntimeException si el conteo ya arranco
     */
    public function iniciar(ConteoInventario $conteo, User $usuario): ConteoInventario
    {
        if ($conteo->estado !== EstadoConteo::Borrador) {
            throw new RuntimeException(
                "El conteo {$conteo->numero_conteo} ya esta {$conteo->estado->label()}."
            );
        }

        return DB::transaction(function () use ($conteo, $usuario): ConteoInventario {
            $existencias = $this->existencias
                ->consulta([
                    'almacen_id' => $conteo->almacen_id,
                    'solo_con_existencia' => true,
                ], $conteo->ubicacion_id !== null)
                ->get();

            foreach ($existencias as $fila) {
                if ($conteo->ubicacion_id !== null && (int) ($fila->ubicacion_id ?? 0) !== (int) $conteo->ubicacion_id) {
                    continue;
                }

                $conteo->lineas()->updateOrCreate(
                    ['producto_id' => $fila->producto_id],
                    [
                        // La existencia FISICA, no la disponible: el almacenista
                        // cuenta lo que hay en el anaquel, y lo apartado sigue
                        // ahi hasta que alguien lo surta.
                        'cantidad_esperada' => $this->existencias->fisica(
                            (int) $fila->producto_id,
                            (int) $conteo->almacen_id,
                        ),
                        'cantidad_contada' => null,
                        'diferencia' => 0,
                    ],
                );
            }

            $conteo->estado = EstadoConteo::EnProceso;
            $conteo->contado_por = $usuario->id;
            $conteo->save();

            $conteo->registrarBitacora('iniciado', [], [
                'estado' => EstadoConteo::EnProceso->value,
                'lineas' => $conteo->lineas()->count(),
            ]);

            return $conteo->refresh();
        });
    }

    /**
     * Guarda lo contado y calcula las diferencias.
     *
     * @param  array<int, float|null>  $cantidades  linea_id => cantidad contada
     *
     * @throws RuntimeException si el conteo no admite captura
     */
    public function capturar(ConteoInventario $conteo, array $cantidades): ConteoInventario
    {
        if (! $conteo->estado->admiteCaptura()) {
            throw new RuntimeException(
                "El conteo {$conteo->numero_conteo} esta {$conteo->estado->label()} y ya no admite captura."
            );
        }

        return DB::transaction(function () use ($conteo, $cantidades): ConteoInventario {
            foreach ($conteo->lineas as $linea) {
                if (! array_key_exists($linea->id, $cantidades)) {
                    continue;
                }

                $contada = $cantidades[$linea->id];

                $linea->cantidad_contada = $contada;
                $linea->diferencia = $contada === null
                    ? 0
                    : (float) $contada - (float) $linea->cantidad_esperada;
                $linea->save();
            }

            // Contado, no cerrado: alguien con `inventario.conteos.cerrar` tiene
            // que revisar las diferencias antes de que toquen el inventario.
            if ($conteo->lineas()->whereNotNull('cantidad_contada')->count() > 0) {
                $conteo->estado = EstadoConteo::Contado;
                $conteo->contado_en = now();
                $conteo->save();
            }

            return $conteo->refresh();
        });
    }

    /**
     * Cierra el conteo: cada diferencia distinta de cero se vuelve un
     * movimiento `conteo` con su signo, y el inventario queda igual a lo contado.
     *
     * @throws RuntimeException si el conteo no esta contado
     */
    public function cerrar(ConteoInventario $conteo, User $usuario): ConteoInventario
    {
        if (! in_array($conteo->estado, [EstadoConteo::Contado, EstadoConteo::Ajustado], true)) {
            throw new RuntimeException(
                "Solo se cierra un conteo ya capturado; {$conteo->numero_conteo} esta {$conteo->estado->label()}."
            );
        }

        return DB::transaction(function () use ($conteo, $usuario): ConteoInventario {
            $ajustadas = 0;

            foreach ($conteo->lineas as $linea) {
                $diferencia = (float) $linea->diferencia;

                if (abs($diferencia) < 0.000001) {
                    continue;
                }

                $this->movimientos->registrar(
                    TipoMovimiento::Conteo,
                    (int) $linea->producto_id,
                    (int) $conteo->almacen_id,
                    // `conteo` no tiene signo propio: se manda tal cual.
                    $diferencia,
                    $this->existencias->costoPromedio((int) $linea->producto_id, (int) $conteo->almacen_id),
                    $conteo,
                    ['ubicacion_id' => $conteo->ubicacion_id, 'organizacion_id' => $conteo->organizacion_id],
                );

                $ajustadas++;
            }

            $conteo->estado = EstadoConteo::Cerrado;
            $conteo->cerrado_por = $usuario->id;
            $conteo->cerrado_en = now();
            $conteo->save();

            $conteo->registrarBitacora('cerrado', [], [
                'estado' => EstadoConteo::Cerrado->value,
                'lineas_ajustadas' => $ajustadas,
            ]);

            return $conteo->refresh();
        });
    }

    /**
     * Agrega al conteo un producto que aparecio y no estaba en la lista.
     *
     * Su cantidad esperada es la del sistema en ese momento, que normalmente
     * sera cero: justo el hallazgo que interesa registrar.
     *
     * @throws RuntimeException si el conteo no admite captura
     */
    public function agregarProducto(ConteoInventario $conteo, Producto $producto): void
    {
        if (! $conteo->estado->admiteCaptura()) {
            throw new RuntimeException('Solo se agregan productos a un conteo en proceso.');
        }

        $conteo->lineas()->firstOrCreate(
            ['producto_id' => $producto->id],
            [
                'cantidad_esperada' => $this->existencias->fisica((int) $producto->id, (int) $conteo->almacen_id),
                'diferencia' => 0,
            ],
        );
    }
}
