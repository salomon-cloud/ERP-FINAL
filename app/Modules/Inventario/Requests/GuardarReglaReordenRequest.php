<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/**
 * El minimo y el maximo de un producto en un almacen.
 *
 * Solo puede haber una regla por par producto-almacen (uq_regla_reorden): dos
 * minimos distintos para el mismo anaquel no significarian nada.
 */
class GuardarReglaReordenRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => [
                'required', 'integer', 'exists:productos,id',
                Rule::unique('reglas_reorden', 'producto_id')
                    ->ignore($this->idEnRuta('regla'))
                    ->where('almacen_id', $this->integer('almacen_id'))
                    ->whereNull('deleted_at'),
            ],
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'cantidad_minima' => ['required', 'numeric', 'min:0'],
            'cantidad_maxima' => ['required', 'numeric', 'min:0'],
            'cantidad_reorden' => ['nullable', 'numeric', 'min:0'],
            'dias_entrega' => ['nullable', 'integer', 'min:0', 'max:365'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'producto_id.unique' => 'Ese producto ya tiene una regla de reorden en este almacen.',
        ]);
    }
}
