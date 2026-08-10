<?php

declare(strict_types=1);

namespace App\Modules\Compras\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Compras\Models\Recepcion;

/**
 * Folio de una recepcion: REC-000001.
 *
 * Se reserva al crear el documento, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_recepcion` no es fillable, asi que una peticion
 * no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorRecepcion
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(Recepcion $documento): void
    {
        if (blank($documento->numero_recepcion)) {
            $documento->numero_recepcion = $this->folios->siguiente('recepciones');
        }
    }
}
