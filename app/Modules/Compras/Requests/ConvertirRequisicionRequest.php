<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/** Convertir una requisicion aprobada en orden de compra: falta elegir proveedor. */
class ConvertirRequisicionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
        ];
    }
}
