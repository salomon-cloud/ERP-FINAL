<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use App\Modules\Ventas\Enums\FormaPagoCobro;
use Illuminate\Validation\Rule;

/**
 * Un cobro.
 *
 * La factura es opcional: sin ella es un cobro a cuenta. Que el monto no exceda
 * el saldo se valida en ServicioCobro, que es quien conoce lo ya cobrado.
 */
class GuardarCobroRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'factura_id' => ['nullable', 'integer', 'exists:facturas,id'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'forma_pago' => ['required', Rule::enum(FormaPagoCobro::class)],
            'referencia' => ['nullable', 'string', 'max:120'],
            'cuenta_bancaria_id' => ['nullable', 'integer', 'exists:cuentas_bancarias,id'],
        ];
    }
}
