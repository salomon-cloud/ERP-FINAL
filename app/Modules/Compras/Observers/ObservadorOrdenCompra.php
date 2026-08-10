<?php

declare(strict_types=1);

namespace App\Modules\Compras\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Compras\Models\OrdenCompra;

/**
 * Folio de una orden de compra: OC-000001.
 *
 * Se reserva al crear el documento, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_orden` no es fillable, asi que una peticion
 * no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorOrdenCompra
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(OrdenCompra $documento): void
    {
        if (blank($documento->numero_orden)) {
            $documento->numero_orden = $this->folios->siguiente('ordenes_compra');
        }
    }
}
