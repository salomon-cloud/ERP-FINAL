<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\Prospecto;

final class ServicioConversionProspecto
{
    public function convertir(Prospecto $prospecto): Prospecto
    {
        return $prospecto;
    }
}