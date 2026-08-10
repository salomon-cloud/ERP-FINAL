<?php

declare(strict_types=1);

namespace App\Modules\Compras\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Compras\Models\Pago;

/**
 * Folio de un pago a proveedor: PAG-000001.
 *
 * Se reserva al crear el documento, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_pago` no es fillable, asi que una peticion
 * no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorPago
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(Pago $documento): void
    {
        if (blank($documento->numero_pago)) {
            $documento->numero_pago = $this->folios->siguiente('pagos');
        }
    }
}
