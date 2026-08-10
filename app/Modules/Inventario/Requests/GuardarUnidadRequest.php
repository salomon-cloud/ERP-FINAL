<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una unidad de medida.
 *
 * El factor tiene que ser mayor que cero (chk_unidades_factor): con factor cero
 * una caja no valdria ninguna pieza y toda conversion daria cero.
 */
class GuardarUnidadRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:10',
                Rule::unique('unidades_medida', 'codigo')->ignore($this->idEnRuta('unidad')),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'factor_base' => ['required', 'numeric', 'gt:0'],
            'es_base' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['es_base' => $this->boolean('es_base')]);
    }
}
