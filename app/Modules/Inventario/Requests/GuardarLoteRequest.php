<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/**
 * Un lote de un producto, capturado desde su ficha.
 *
 * El numero es unico por producto (uq_lotes_producto_numero): dos proveedores
 * distintos pueden usar el mismo numero de lote para articulos distintos.
 */
class GuardarLoteRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'numero_lote' => [
                'required', 'string', 'max:60',
                Rule::unique('lotes', 'numero_lote')
                    ->where('producto_id', $this->integer('producto_id'))
                    ->whereNull('deleted_at'),
            ],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'fecha_caducidad' => ['nullable', 'date'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo', true)]);
    }
}
