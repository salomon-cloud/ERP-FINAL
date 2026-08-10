<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

/**
 * Cabecera de un traspaso.
 *
 * `different` repite en la validacion lo que chk_traspasos_almacenes ya impide
 * en la base: traspasar un almacen a si mismo. La base es la ultima red, no la
 * unica -- sin esta regla el usuario veria un error de integridad crudo.
 */
class GuardarTraspasoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'almacen_origen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'almacen_destino_id' => ['required', 'integer', 'exists:almacenes,id', 'different:almacen_origen_id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'almacen_destino_id.different' => 'El almacen de destino debe ser distinto del de origen.',
        ]);
    }
}
