<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Ventas\Models\NotaCredito;

/**
 * Folio de una nota de credito: NC-000001.
 *
 * Se reserva al crear el documento, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_nota` no es fillable, asi que una peticion
 * no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorNotaCredito
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(NotaCredito $documento): void
    {
        if (blank($documento->numero_nota)) {
            $documento->numero_nota = $this->folios->siguiente('notas_credito');
        }
    }
}
