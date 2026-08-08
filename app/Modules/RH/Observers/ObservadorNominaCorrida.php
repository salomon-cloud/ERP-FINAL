<?php

declare(strict_types=1);

namespace App\Modules\RH\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\RH\Models\NominaCorrida;

/**
 * Le pone folio a la corrida al crearla: NOM-000001.
 *
 * El numero se reserva en el momento del alta, aunque la corrida nazca en
 * borrador, y jamas vuelve al pozo si despues se cancela (PLANNING -
 * "Sequences"). El contador vive en secuencias_documento bajo el modulo
 * `nomina_corridas`, sembrado por SecuenciaDocumentoSeeder.
 *
 * `numero_corrida` no es fillable, asi que una peticion no puede elegir su
 * propio folio; el hueco de abajo es solo para las pruebas y las migraciones de
 * datos, que si necesitan fijarlo.
 */
class ObservadorNominaCorrida
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(NominaCorrida $corrida): void
    {
        if (blank($corrida->numero_corrida)) {
            $corrida->numero_corrida = $this->folios->siguiente('nomina_corridas');
        }
    }
}
