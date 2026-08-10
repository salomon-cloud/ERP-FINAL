<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use App\Modules\Compartido\Requests\RequestBase as RequestCompartida;

/**
 * La base de los FormRequest de Inventario.
 *
 * Hereda los mensajes en espanol de Compartido y agrega los nombres de campo
 * propios del modulo, para que un error diga "el campo numero de lote" y no
 * "el campo numero_lote".
 */
abstract class RequestBase extends RequestCompartida
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'almacen_origen_id' => 'almacen de origen',
            'almacen_destino_id' => 'almacen de destino',
            'numero_lote' => 'numero de lote',
            'numero_serie' => 'numero de serie',
            'factor_base' => 'factor de conversion',
            'es_base' => 'es unidad base',
            'es_surtible' => 'permite surtir',
            'es_vendible' => 'se puede vender',
            'es_comprable' => 'se puede comprar',
            'es_inventariable' => 'lleva inventario',
            'rastrea_serie' => 'rastrea numero de serie',
            'es_principal' => 'codigo principal',
            'cantidad_minima' => 'cantidad minima',
            'cantidad_maxima' => 'cantidad maxima',
            'cantidad_reorden' => 'cantidad a reordenar',
            'cantidad_contada' => 'cantidad contada',
            'cantidad_esperada' => 'cantidad esperada',
            'dias_entrega' => 'dias de entrega',
            'tipo_movimiento' => 'tipo de movimiento',
        ]);
    }
}
