<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/**
 * Cabecera de una orden de compra.
 *
 * `after_or_equal` repite lo que ya impide chk_ordenes_compra_fechas: una
 * entrega no puede ser anterior al pedido. La base es la ultima red, no la
 * unica -- sin esta regla el usuario veria un error de integridad crudo.
 */
class GuardarOrdenCompraRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha' => ['required', 'date'],
            'fecha_entrega' => ['nullable', 'date', 'after_or_equal:fecha'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
