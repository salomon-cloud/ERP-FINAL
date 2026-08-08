<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoContrato;
use App\Modules\RH\Enums\TipoContrato;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un contrato.
 *
 * `fecha_fin` nula es lo que distingue a un contrato indefinido, asi que se
 * acepta vacia a proposito.
 *
 * Ojo con `sueldo`: es el del contrato firmado, para efectos legales e
 * historicos. Lo que la nomina paga es empleados.sueldo_base, y son dos cosas
 * distintas (ver el modelo Contrato).
 */
class GuardarContratoRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'numero_contrato' => ['nullable', 'string', 'max:30'],
            'tipo_contrato' => ['required', Rule::enum(TipoContrato::class)],
            'fecha_inicio' => ['required', 'date'],

            // chk_contratos_fechas
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],

            // chk_contratos_valores: sueldo >= 0 y jornada > 0
            'sueldo' => ['required', 'numeric', 'min:0'],
            'jornada_horas' => ['required', 'numeric', 'gt:0'],

            'resumen_clausulas' => ['nullable', 'string'],
            'estado' => ['required', Rule::enum(EstadoContrato::class)],
            'firmado_en' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'numero_contrato' => 'numero de contrato',
            'jornada_horas' => 'jornada semanal en horas',
            'resumen_clausulas' => 'resumen de clausulas',
            'firmado_en' => 'fecha de firma',
        ]);
    }
}
