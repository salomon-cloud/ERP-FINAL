<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Models\User;
use App\Modules\Inventario\Enums\EstadoAjuste;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\AjusteInventario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Aplicar un ajuste: la unica forma legitima de cambiar existencia sin un
 * documento comercial detras.
 *
 * Por eso aplicar exige `inventario.ajustes.aprobar` y no basta con poder
 * editarlo: capturar el ajuste y autorizarlo son dos actos distintos, y la
 * autorizacion queda con nombre y fecha en `aprobado_por` / `aprobado_en`.
 */
class ServicioAjusteInventario
{
    public function __construct(
        private readonly ServicioMovimientoInventario $movimientos,
        private readonly ServicioExistencias $existencias,
    ) {}

    /**
     * Convierte cada linea en su movimiento y cierra el ajuste.
     *
     * El signo de `diferencia` decide el tipo: sobra -> entrada, falta ->
     * salida. Un ajuste negativo se valoriza al costo promedio del producto
     * cuando la linea no trae costo, porque dar de baja mercancia al costo de
     * lista y no al que costo distorsiona el valor del inventario.
     *
     * @throws RuntimeException si el ajuste no esta en borrador o no tiene lineas
     */
    public function aplicar(AjusteInventario $ajuste, User $usuario): AjusteInventario
    {
        if ($ajuste->estado !== EstadoAjuste::Borrador) {
            throw new RuntimeException(
                "El ajuste {$ajuste->numero_ajuste} esta {$ajuste->estado->label()} y ya no se puede aplicar."
            );
        }

        if ($ajuste->lineas()->count() === 0) {
            throw new RuntimeException('Un ajuste sin lineas no se puede aplicar.');
        }

        return DB::transaction(function () use ($ajuste, $usuario): AjusteInventario {
            foreach ($ajuste->lineas as $linea) {
                $diferencia = (float) $linea->diferencia;

                $costo = (float) $linea->costo_unitario > 0
                    ? (float) $linea->costo_unitario
                    : $this->existencias->costoPromedio((int) $linea->producto_id, (int) $linea->almacen_id);

                $this->movimientos->registrar(
                    $diferencia > 0 ? TipoMovimiento::AjusteEntrada : TipoMovimiento::AjusteSalida,
                    (int) $linea->producto_id,
                    (int) $linea->almacen_id,
                    abs($diferencia),
                    $costo,
                    $ajuste,
                    ['ubicacion_id' => $linea->ubicacion_id, 'organizacion_id' => $ajuste->organizacion_id],
                );
            }

            $ajuste->estado = EstadoAjuste::Aplicado;
            $ajuste->aprobado_por = $usuario->id;
            $ajuste->aprobado_en = now();
            $ajuste->aplicado_en = now();
            $ajuste->save();

            $ajuste->registrarBitacora('aplicado', [], ['estado' => EstadoAjuste::Aplicado->value]);

            return $ajuste->refresh();
        });
    }

    /**
     * Cancela un ajuste que todavia no se aplico.
     *
     * Uno ya aplicado no se cancela: se corrige con otro ajuste en sentido
     * contrario, para que las dos decisiones queden en el kardex.
     *
     * @throws RuntimeException
     */
    public function cancelar(AjusteInventario $ajuste): AjusteInventario
    {
        if (! $ajuste->estado->esCancelable()) {
            throw new RuntimeException(
                "El ajuste {$ajuste->numero_ajuste} ya esta {$ajuste->estado->label()}: ".
                'corrigelo con otro ajuste en sentido contrario.'
            );
        }

        $ajuste->estado = EstadoAjuste::Cancelado;
        $ajuste->save();

        $ajuste->registrarBitacora('cancelado', [], ['estado' => EstadoAjuste::Cancelado->value]);

        return $ajuste->refresh();
    }
}
