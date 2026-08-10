<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/** Cabecera de una recepcion: contra que orden y a que almacen entra. */
class GuardarRecepcionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'orden_compra_id' => ['required', 'integer', 'exists:ordenes_compra,id'],
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'fecha' => ['required', 'date'],
        ];
    }
}
