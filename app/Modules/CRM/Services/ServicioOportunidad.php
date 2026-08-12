<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\Oportunidad;

final class ServicioOportunidad
{
    public function cambiarEtapa(Oportunidad $oportunidad, string $etapa): Oportunidad
    {
        $oportunidad->etapa = $etapa;

        return $oportunidad;
    }
}