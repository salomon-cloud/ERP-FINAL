<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/** Cabecera de una factura capturada a mano (sin pedido detras). */
class GuardarFacturaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'pedido_id' => ['nullable', 'integer', 'exists:pedidos,id'],
            'periodo_fiscal_id' => ['nullable', 'integer', 'exists:periodos_fiscales,id'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:catalogos,id'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
