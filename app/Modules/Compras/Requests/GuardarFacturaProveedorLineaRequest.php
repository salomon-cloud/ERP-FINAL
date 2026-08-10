<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/** Un renglon de factura de proveedor. */
class GuardarFacturaProveedorLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'orden_compra_linea_id' => ['nullable', 'integer', 'exists:orden_compra_lineas,id'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'impuesto_id' => ['nullable', 'integer', 'exists:impuestos,id'],
        ];
    }
}
