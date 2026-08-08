<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoAsistencia;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un registro de asistencia.
 *
 * `horas_trabajadas` NO se valida ni se recibe: lo deriva ObservadorAsistencia
 * de la entrada y la salida. Aceptarlo aqui abriria la puerta a que la pantalla
 * mandara un numero que contradijera a sus propias horas.
 */
class GuardarAsistenciaRequest extends RequestBase
{
    public function rules(): array
    {
        $id = $this->idEnRuta('asistencia');

        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],

            // uq_asistencias_empleado_fecha: un empleado, un registro por dia.
            // El indice abarca las filas con borrado logico, asi que la regla
            // tampoco las excluye.
            'fecha' => [
                'required', 'date',
                Rule::unique('asistencias', 'fecha')
                    ->where(fn (Builder $consulta) => $consulta->where('empleado_id', $this->input('empleado_id')))
                    ->ignore($id),
            ],

            'hora_entrada' => ['nullable', 'date_format:H:i,H:i:s'],

            // chk_asistencias_horario: no se puede salir antes de entrar.
            'hora_salida' => ['nullable', 'date_format:H:i,H:i:s', 'after_or_equal:hora_entrada'],

            'estado' => ['required', Rule::enum(EstadoAsistencia::class)],
            'notas' => ['nullable', 'string', 'max:500'],
            'verificado_por' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'fecha.unique' => 'Ese empleado ya tiene un registro de asistencia en esa fecha.',
        ]);
    }
}
