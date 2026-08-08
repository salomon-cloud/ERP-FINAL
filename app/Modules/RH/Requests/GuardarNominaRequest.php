<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoNomina;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un recibo de nomina.
 *
 * `total_pagar` NO se recibe: lo deriva ObservadorNomina con la formula de
 * Nomina::calcularTotal(). Es dinero, y un total capturado a mano podria
 * contradecir a sus propios conceptos.
 *
 * Los minimos de cada monto son los mismos que exige chk_nominas_montos.
 */
class GuardarNominaRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'corrida_id' => ['nullable', 'integer', 'exists:nomina_corridas,id'],
            'periodo_pago' => ['required', 'string', 'max:255'],
            'fecha_pago' => ['required', 'date'],

            'sueldo_base' => ['required', 'numeric', 'min:0'],
            'bonos' => ['required', 'numeric', 'min:0'],
            'horas_extra' => ['required', 'numeric', 'min:0'],
            'horas_extra_cantidad' => ['required', 'numeric', 'min:0'],
            'deducciones' => ['required', 'numeric', 'min:0'],
            'dias_ausencia' => ['required', 'numeric', 'min:0'],
            'isr' => ['required', 'numeric', 'min:0'],
            'imss' => ['required', 'numeric', 'min:0'],

            'estado' => ['required', Rule::enum(EstadoNomina::class)],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'periodo_pago' => 'periodo de pago',
            'horas_extra' => 'importe de horas extra',
            'horas_extra_cantidad' => 'cantidad de horas extra',
            'dias_ausencia' => 'dias de ausencia',
            'isr' => 'ISR',
            'imss' => 'IMSS',
        ]);
    }
}
