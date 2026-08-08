<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoNominaPeriodo;
use App\Modules\RH\Enums\FrecuenciaPago;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un periodo de nomina (la quincena que se va a pagar).
 *
 * uq_nomina_periodos_codigo es un unico normal, no de columna generada: abarca
 * las filas con borrado logico, y por eso la regla tampoco las excluye.
 */
class GuardarNominaPeriodoRequest extends RequestBase
{
    public function rules(): array
    {
        $id = $this->idEnRuta('nomina_periodo');

        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id'],

            'codigo_periodo' => [
                'required', 'string', 'max:30',
                Rule::unique('nomina_periodos', 'codigo_periodo')->ignore($id),
            ],

            'fecha_inicio' => ['required', 'date'],

            // chk_nomina_periodo_fechas
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],

            // Se paga al cerrar el periodo, nunca antes de que termine.
            'fecha_pago' => ['required', 'date', 'after_or_equal:fecha_fin'],

            'frecuencia' => ['required', Rule::enum(FrecuenciaPago::class)],
            'estado' => ['required', Rule::enum(EstadoNominaPeriodo::class)],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'codigo_periodo' => 'codigo del periodo',
        ]);
    }
}
