<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoEvaluacionDesempeno;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una evaluacion de desempeno.
 *
 * `objetivos` es una lista de metas con su medicion; se guarda como JSON. Se
 * valida su forma campo por campo para que la columna no termine con basura que
 * despues nadie pueda pintar.
 */
class GuardarEvaluacionDesempenoRequest extends RequestBase
{
    public function rules(): array
    {
        // El nombre del parametro lo fija Routes/web.php: {evaluacion}.
        $id = $this->idEnRuta('evaluacion');

        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'evaluador_id' => ['nullable', 'integer', 'exists:empleados,id'],

            // uq_evaluacion_periodo: una evaluacion por empleado y periodo.
            'periodo_evaluado' => [
                'required', 'string', 'max:50',
                Rule::unique('evaluaciones_desempeno', 'periodo_evaluado')
                    ->where(fn (Builder $consulta) => $consulta->where('empleado_id', $this->input('empleado_id')))
                    ->ignore($id),
            ],

            // chk_evaluaciones_calificacion
            'calificacion' => ['nullable', 'numeric', 'between:0,100'],

            'fortalezas' => ['nullable', 'string'],
            'areas_mejora' => ['nullable', 'string'],

            'objetivos' => ['nullable', 'array'],
            'objetivos.*.objetivo' => ['required', 'string', 'max:255'],
            'objetivos.*.metrica' => ['nullable', 'string', 'max:120'],
            'objetivos.*.meta' => ['nullable', 'string', 'max:120'],
            'objetivos.*.logrado' => ['nullable', 'boolean'],

            'estado' => ['required', Rule::enum(EstadoEvaluacionDesempeno::class)],
            'evaluado_en' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'periodo_evaluado.unique' => 'Ese empleado ya tiene una evaluacion de ese periodo.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'periodo_evaluado' => 'periodo evaluado',
            'areas_mejora' => 'areas de mejora',
            'evaluado_en' => 'fecha de evaluacion',
        ]);
    }
}
