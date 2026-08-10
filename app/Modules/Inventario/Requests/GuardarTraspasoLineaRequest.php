<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

/** Un renglon de traspaso. El costo es opcional: si falta, se toma el promedio. */
class GuardarTraspasoLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'lote_id' => ['nullable', 'integer', 'exists:lotes,id'],
        ];
    }
}
