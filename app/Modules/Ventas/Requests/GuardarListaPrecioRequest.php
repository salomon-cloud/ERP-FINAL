<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use Illuminate\Validation\Rule;

/** Alta y edicion de una lista de precios. */
class GuardarListaPrecioRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('listas_precios', 'codigo')
                    ->ignore($this->idEnRuta('lista'))
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'moneda' => ['required', 'string', 'size:3'],
            'es_predeterminada' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['es_predeterminada' => $this->boolean('es_predeterminada')]);
    }
}
