<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Ventas\Models\Cotizacion;

/**
 * Folio de una cotizacion: COT-000001.
 *
 * Se reserva al crear el documento, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_cotizacion` no es fillable, asi que una peticion
 * no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorCotizacion
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(Cotizacion $documento): void
    {
        if (blank($documento->numero_cotizacion)) {
            $documento->numero_cotizacion = $this->folios->siguiente('cotizaciones');
        }
    }
}
