<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Enums\FormaPagoProveedor;
use Illuminate\Validation\Rule;

/**
 * Un pago a proveedor.
 *
 * La factura es opcional: sin ella es un pago a cuenta. Que el monto no exceda
 * el saldo se valida en ServicioPago, que es quien conoce lo ya pagado.
 */
class GuardarPagoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'factura_proveedor_id' => ['nullable', 'integer', 'exists:facturas_proveedor,id'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'forma_pago' => ['required', Rule::enum(FormaPagoProveedor::class)],
            'referencia' => ['nullable', 'string', 'max:120'],
            'cuenta_bancaria_id' => ['nullable', 'integer', 'exists:cuentas_bancarias,id'],
        ];
    }
}
