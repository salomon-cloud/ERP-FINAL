<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

/** Cabecera de un ajuste. El motivo es obligatorio y va a la bitacora. */
class GuardarAjusteRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:500'],
        ];
    }
}
