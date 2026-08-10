<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Inventario\Models\AjusteInventario;

/** Folio del ajuste: AJU-000001. Ver ObservadorTraspaso. */
class ObservadorAjusteInventario
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(AjusteInventario $ajuste): void
    {
        if (blank($ajuste->numero_ajuste)) {
            $ajuste->numero_ajuste = $this->folios->siguiente('ajustes_inventario');
        }
    }
}
