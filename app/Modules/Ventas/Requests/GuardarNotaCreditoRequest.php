<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use App\Modules\Ventas\Enums\MotivoNotaCredito;
use Illuminate\Validation\Rule;

/**
 * Cabecera de una nota de credito.
 *
 * El motivo no es decorativo: solo `devolucion` regresa mercancia al almacen.
 */
class GuardarNotaCreditoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'factura_id' => ['required', 'integer', 'exists:facturas,id'],
            'motivo' => ['required', Rule::enum(MotivoNotaCredito::class)],
            'fecha_emision' => ['required', 'date'],
        ];
    }
}
