<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoActivacion;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un departamento.
 *
 * Una sola peticion para las dos mutaciones porque las reglas son identicas:
 * lo unico que cambia es que al editar los `unique` ignoran la propia fila, y
 * eso lo resuelve idEnRuta(). Si algun dia una de las dos necesita reglas
 * propias, se extiende esta clase en vez de duplicarla.
 */
class GuardarDepartamentoRequest extends RequestBase
{
    public function rules(): array
    {
        $id = $this->idEnRuta('departamento');

        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id'],

            // Un departamento no puede colgar de si mismo.
            'padre_id' => ['nullable', 'integer', 'exists:departamentos,id', Rule::notIn(array_filter([$id]))],

            'jefe_id' => ['nullable', 'integer', 'exists:empleados,id'],

            // uq_departamentos_codigo se apoya en una columna generada que vale
            // NULL en las filas borradas, asi que un codigo liberado por un
            // borrado logico se puede reutilizar.
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('departamentos', 'codigo')->whereNull('deleted_at')->ignore($id),
            ],

            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'padre_id.not_in' => 'Un departamento no puede ser su propio padre.',
        ]);
    }
}
