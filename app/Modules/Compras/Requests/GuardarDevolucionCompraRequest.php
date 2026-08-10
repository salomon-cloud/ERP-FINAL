<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Enums\MotivoDevolucionCompra;
use Illuminate\Validation\Rule;

/** Cabecera de una devolucion a proveedor. */
class GuardarDevolucionCompraRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'factura_proveedor_id' => ['required', 'integer', 'exists:facturas_proveedor,id'],
            'motivo' => ['required', Rule::enum(MotivoDevolucionCompra::class)],
            'fecha' => ['required', 'date'],
        ];
    }
}
