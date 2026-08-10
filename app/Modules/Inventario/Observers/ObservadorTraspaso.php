<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Inventario\Models\Traspaso;

/**
 * Le pone folio al traspaso al crearlo: TRA-000001.
 *
 * El numero se reserva en el alta, aunque nazca en borrador, y jamas vuelve al
 * pozo si despues se cancela. `numero_traspaso` no es fillable, asi que una
 * peticion no puede elegir el suyo; el hueco de abajo es para las pruebas y las
 * migraciones de datos, que si necesitan fijarlo.
 */
class ObservadorTraspaso
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(Traspaso $traspaso): void
    {
        if (blank($traspaso->numero_traspaso)) {
            $traspaso->numero_traspaso = $this->folios->siguiente('traspasos');
        }
    }
}
