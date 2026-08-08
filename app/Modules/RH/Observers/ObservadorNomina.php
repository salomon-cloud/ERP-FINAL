<?php

declare(strict_types=1);

namespace App\Modules\RH\Observers;

use App\Modules\RH\Models\Nomina;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene cuadrados los dos totales derivados de la nomina:
 *
 *   1. `nominas.total_pagar`, que es percepciones menos deducciones del recibo.
 *   2. Los totales de la corrida a la que pertenece el recibo.
 *
 * El total del recibo se recalcula SIEMPRE. Es un dato derivado y es dinero: si
 * se permitiera capturarlo a mano podria contradecir a sus propios conceptos.
 * La formula no se copia aqui, se le pide a Nomina::calcularTotal().
 */
class ObservadorNomina
{
    public function saving(Nomina $nomina): void
    {
        $nomina->total_pagar = Nomina::calcularTotal($nomina->attributesToArray());
    }

    public function saved(Nomina $nomina): void
    {
        $this->sincronizarCorrida($nomina->corrida_id);

        // Si el recibo cambio de corrida, la anterior tambien queda desfasada.
        $anterior = $nomina->getOriginal('corrida_id');

        if ($anterior !== null && (int) $anterior !== (int) $nomina->corrida_id) {
            $this->sincronizarCorrida((int) $anterior);
        }
    }

    public function deleted(Nomina $nomina): void
    {
        $this->sincronizarCorrida($nomina->corrida_id);
    }

    public function restored(Nomina $nomina): void
    {
        $this->sincronizarCorrida($nomina->corrida_id);
    }

    /**
     * Recalcula los totales de una corrida sumando sus recibos vivos.
     *
     * Se actualiza con el query builder, no con Eloquent, por dos razones: es
     * una sola consulta en vez de traer todos los recibos a memoria, y no
     * dispara los eventos del modelo -- si los disparara, generar una corrida de
     * cien empleados dejaria cien renglones de "corrida actualizada" en la
     * bitacora, que no le sirven a nadie.
     *
     * Cuentan todos los recibos no eliminados. Cuando ServicioCorridaNomina
     * defina la cancelacion de un recibo suelto, ahi se decide si los cancelados
     * dejan de sumar.
     */
    private function sincronizarCorrida(?int $corridaId): void
    {
        if ($corridaId === null) {
            return;
        }

        $totales = Nomina::query()
            ->where('corrida_id', $corridaId)
            ->selectRaw('COUNT(*) AS empleados')
            ->selectRaw('COALESCE(SUM(sueldo_base + bonos + horas_extra), 0) AS percepciones')
            ->selectRaw('COALESCE(SUM(deducciones + isr + imss), 0) AS deducciones')
            ->selectRaw('COALESCE(SUM(total_pagar), 0) AS neto')
            ->first();

        DB::table('nomina_corridas')->where('id', $corridaId)->update([
            'total_empleados' => (int) $totales->empleados,
            'total_percepciones' => $totales->percepciones,
            'total_deducciones' => $totales->deducciones,
            'total_neto' => $totales->neto,
            'updated_at' => now(),
        ]);
    }
}
