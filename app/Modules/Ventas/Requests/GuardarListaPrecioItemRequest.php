<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use Illuminate\Validation\Rule;

/**
 * Un escalon de precio: este producto, a partir de esta cantidad, cuesta esto.
 *
 * La combinacion lista + producto + cantidad_minima es unica
 * (uq_lista_precio_item): dos precios para el mismo escalon no significarian
 * nada.
 */
class GuardarListaPrecioItemRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => [
                'required', 'integer', 'exists:productos,id',
                Rule::unique('lista_precio_items', 'producto_id')
                    ->where('lista_precio_id', $this->idEnRuta('lista'))
                    ->where('cantidad_minima', $this->input('cantidad_minima', 1)),
            ],
            'cantidad_minima' => ['required', 'numeric', 'gt:0'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'producto_id.unique' => 'Ese producto ya tiene un precio para esa cantidad minima en esta lista.',
        ]);
    }
}
