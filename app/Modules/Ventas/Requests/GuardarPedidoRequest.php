<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/** Cabecera de un pedido. */
class GuardarPedidoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'lista_precio_id' => ['nullable', 'integer', 'exists:listas_precios,id'],
            'fecha' => ['required', 'date'],
            'fecha_entrega' => ['nullable', 'date', 'after_or_equal:fecha'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
