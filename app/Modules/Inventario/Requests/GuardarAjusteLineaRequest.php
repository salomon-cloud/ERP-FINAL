<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Validator;

/**
 * Un renglon de ajuste.
 *
 * La `diferencia` va con signo y NO puede ser cero: chk_ajuste_linea_diferencia
 * lo impide en la base porque un ajuste de cero no ajusta nada.
 */
class GuardarAjusteLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'ubicacion_id' => ['nullable', 'integer', 'exists:ubicaciones,id'],
            'diferencia' => ['required', 'numeric'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $validador->after(function (Validator $validador): void {
            if (abs((float) $this->input('diferencia', 0)) < 0.000001) {
                $validador->errors()->add('diferencia', 'La diferencia no puede ser cero.');
            }
        });
    }
}
