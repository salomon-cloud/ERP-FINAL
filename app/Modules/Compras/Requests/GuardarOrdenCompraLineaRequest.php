<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use Illuminate\Validation\Validator;

/**
 * Un renglon de orden de compra.
 *
 * El descuento va en porcentaje O en monto, nunca en los dos: lo impide
 * chk_orden_compra_linea_descuento y se comprueba aqui para dar un mensaje en
 * vez de un error de base de datos.
 */
class GuardarOrdenCompraLineaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'between:0,100'],
            'monto_descuento' => ['nullable', 'numeric', 'min:0'],
            'impuesto_id' => ['nullable', 'integer', 'exists:impuestos,id'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $validador->after(function (Validator $validador): void {
            if ((float) $this->input('porcentaje_descuento', 0) > 0
                && (float) $this->input('monto_descuento', 0) > 0) {
                $validador->errors()->add('monto_descuento',
                    'Captura el descuento en porcentaje o en monto, no en los dos.');
            }
        });
    }
}
