<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use App\Modules\Compartido\Requests\RequestBase as RequestCompartida;

/** La base de los FormRequest de Ventas. Ver App\Modules\Compartido\Requests\RequestBase. */
abstract class RequestBase extends RequestCompartida
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'cotizacion_id' => 'cotizacion',
            'pedido_id' => 'pedido',
            'pedido_linea_id' => 'renglon del pedido',
            'factura_id' => 'factura',
            'factura_linea_id' => 'renglon de la factura',
            'nota_credito_id' => 'nota de credito',
            'cantidad_surtida' => 'cantidad surtida',
            'limite_credito' => 'limite de credito',
            'razon_social' => 'razon social',
            'vigencia' => 'vigencia de la oferta',
            'es_predeterminada' => 'es la lista predeterminada',
            'cantidad_minima' => 'cantidad minima',
        ]);
    }
}
