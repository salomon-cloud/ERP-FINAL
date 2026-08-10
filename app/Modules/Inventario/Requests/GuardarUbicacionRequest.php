<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una ubicacion.
 *
 * El codigo es unico DENTRO del almacen (uq_ubicaciones_almacen_codigo), no en
 * todo el sistema: dos bodegas pueden tener las dos su pasillo "A-01".
 */
class GuardarUbicacionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('ubicaciones', 'codigo')
                    ->ignore($this->idEnRuta('ubicacion'))
                    ->where('almacen_id', $this->integer('almacen_id')),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'es_surtible' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'es_surtible' => $this->boolean('es_surtible'),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
