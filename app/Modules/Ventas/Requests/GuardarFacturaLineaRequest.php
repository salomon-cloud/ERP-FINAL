<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/** Un renglon de factura capturado a mano. */
class GuardarFacturaLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'between:0,100'],
            'impuesto_id' => ['nullable', 'integer', 'exists:impuestos,id'],
        ];
    }
}
