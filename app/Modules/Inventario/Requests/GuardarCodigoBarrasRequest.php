<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/**
 * Un codigo de barras. Es unico en TODO el sistema (uq_codigos_barras_codigo):
 * si dos productos compartieran codigo, la pistola no sabria cual leer.
 */
class GuardarCodigoBarrasRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:80', Rule::unique('codigos_barras', 'codigo')],
            'es_principal' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['es_principal' => $this->boolean('es_principal')]);
    }
}
