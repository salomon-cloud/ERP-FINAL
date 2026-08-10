<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use App\Modules\Compartido\Enums\EstadoActivacion;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un proveedor.
 *
 * El codigo es unico entre los ACTIVOS, igual que el indice de la base: al
 * ignorar los borrados logicos, un codigo se puede reutilizar despues de dar de
 * baja al proveedor que lo tenia.
 */
class GuardarProveedorRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('proveedores', 'codigo')
                    ->ignore($this->idEnRuta('proveedor'))
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:200'],
            'razon_social' => ['nullable', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'max:30'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:catalogos,id'],
            'moneda' => ['required', 'string', 'size:3'],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
        ];
    }
}
