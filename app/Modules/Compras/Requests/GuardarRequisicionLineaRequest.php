<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/** Un renglon de requisicion: sin precio, porque quien pide no cotiza. */
class GuardarRequisicionLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad_solicitada' => ['required', 'numeric', 'gt:0'],
            'proveedor_sugerido_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
