<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

use App\Modules\Compartido\Requests\RequestBase as RequestCompartida;

/** La base de los FormRequest de Compras. Ver App\Modules\Compartido\Requests\RequestBase. */
abstract class RequestBase extends RequestCompartida
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'orden_compra_id' => 'orden de compra',
            'orden_compra_linea_id' => 'renglon de la orden',
            'factura_proveedor_id' => 'factura de proveedor',
            'factura_proveedor_linea_id' => 'renglon de la factura',
            'requisicion_id' => 'requisicion',
            'recepcion_id' => 'recepcion',
            'proveedor_sugerido_id' => 'proveedor sugerido',
            'cantidad_solicitada' => 'cantidad solicitada',
            'cantidad_recibida' => 'cantidad recibida',
            'razon_social' => 'razon social',
        ]);
    }
}
