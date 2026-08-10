<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/**
 * Un renglon de nota de credito.
 *
 * No se valida aqui que no se acredite de mas: esa regla depende de otras notas
 * sobre el mismo renglon y vive en ServicioNotaCredito.
 */
class GuardarNotaCreditoLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'factura_linea_id' => ['required', 'integer', 'exists:factura_lineas,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
