<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use Illuminate\Validation\Rule;

/**
 * Aprobar o rechazar una requisicion.
 *
 * Es UNA sola peticion porque es una sola decision: el valor de `decision` dice
 * cual de las dos, y la regla `in` se encarga de que no pueda ser otra cosa.
 * Es el mismo patron que RevisarPermisoRequest en RH.
 */
class RevisarRequisicionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'comentario' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function apruebaLaRequisicion(): bool
    {
        return $this->input('decision') === 'aprobar';
    }
}
