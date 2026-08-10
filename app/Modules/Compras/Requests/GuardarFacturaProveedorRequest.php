<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/**
 * Cabecera de una factura de proveedor.
 *
 * El folio del proveedor va en `notas`: es un dato externo que puede repetirse
 * entre proveedores distintos, asi que no sirve como identificador. El folio
 * propio (FP-000001) lo pone el observer.
 */
class GuardarFacturaProveedorRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'recepcion_id' => ['nullable', 'integer', 'exists:recepciones,id'],
            'periodo_fiscal_id' => ['nullable', 'integer', 'exists:periodos_fiscales,id'],
            'fecha' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
