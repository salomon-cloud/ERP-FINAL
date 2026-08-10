<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/**
 * Lo que llego de un renglon de la orden.
 *
 * Aqui NO se valida que no se reciba de mas: esa regla depende de lo ya
 * recibido en otras recepciones y vive en ServicioRecepcion, que es quien puede
 * verla completa.
 */
class GuardarRecepcionLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'orden_compra_linea_id' => ['required', 'integer', 'exists:orden_compra_lineas,id'],
            'cantidad_recibida' => ['required', 'numeric', 'gt:0'],
            'ubicacion_id' => ['nullable', 'integer', 'exists:ubicaciones,id'],
            'lote_id' => ['nullable', 'integer', 'exists:lotes,id'],
            'numero_serie_id' => ['nullable', 'integer', 'exists:numeros_serie,id'],
        ];
    }
}
