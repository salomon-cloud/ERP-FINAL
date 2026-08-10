<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/**
 * Un renglon de devolucion.
 *
 * No se valida aqui que no se devuelva de mas: esa regla depende de otras
 * devoluciones del mismo renglon y vive en ServicioDevolucionCompra.
 */
class GuardarDevolucionCompraLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'factura_proveedor_linea_id' => ['required', 'integer', 'exists:factura_proveedor_lineas,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
