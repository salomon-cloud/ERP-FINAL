<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/** Alta y edicion de un almacen. */
class GuardarAlmacenRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('almacenes', 'codigo')
                    ->ignore($this->idEnRuta('almacen'))
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }
}
