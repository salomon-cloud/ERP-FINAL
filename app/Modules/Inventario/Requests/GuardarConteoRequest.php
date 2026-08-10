<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

/** Cabecera de un conteo fisico: donde se cuenta. */
class GuardarConteoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'ubicacion_id' => ['nullable', 'integer', 'exists:ubicaciones,id'],
        ];
    }
}
