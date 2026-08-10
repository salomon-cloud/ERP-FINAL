<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/**
 * Cabecera de una cotizacion.
 *
 * `after_or_equal` repite lo que ya impide chk_cotizaciones_vigencia: una
 * oferta no puede vencer antes de emitirse.
 */
class GuardarCotizacionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'lista_precio_id' => ['nullable', 'integer', 'exists:listas_precios,id'],
            'fecha' => ['required', 'date'],
            'vigencia' => ['nullable', 'date', 'after_or_equal:fecha'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
