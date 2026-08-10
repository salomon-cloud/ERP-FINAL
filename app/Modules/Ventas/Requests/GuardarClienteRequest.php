<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use App\Modules\Compartido\Enums\EstadoActivacion;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un cliente.
 *
 * El codigo es unico entre los ACTIVOS, igual que el indice de la base: al
 * ignorar los borrados logicos, un codigo se puede reutilizar despues de dar de
 * baja al cliente que lo tenia.
 *
 * El RFC es opcional a proposito: en Mexico se factura a publico en general.
 */
class GuardarClienteRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('clientes', 'codigo')
                    ->ignore($this->idEnRuta('cliente'))
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:200'],
            'razon_social' => ['nullable', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'limite_credito' => ['required', 'numeric', 'min:0'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:catalogos,id'],
            'lista_precio_id' => ['nullable', 'integer', 'exists:listas_precios,id'],
            'moneda' => ['required', 'string', 'size:3'],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
        ];
    }
}
