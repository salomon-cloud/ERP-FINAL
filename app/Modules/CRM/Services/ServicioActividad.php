<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\Actividad;

final class ServicioActividad
{
    public function registrar(Actividad $actividad): Actividad
    {
        return $actividad;
    }
}