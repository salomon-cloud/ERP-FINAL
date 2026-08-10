<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Observers;

use App\Modules\Compartido\Services\ServicioFolios;
use App\Modules\Inventario\Models\ConteoInventario;

/** Folio del conteo: CON-000001. Ver ObservadorTraspaso. */
class ObservadorConteoInventario
{
    public function __construct(private readonly ServicioFolios $folios) {}

    public function creating(ConteoInventario $conteo): void
    {
        if (blank($conteo->numero_conteo)) {
            $conteo->numero_conteo = $this->folios->siguiente('conteos_inventario');
        }
    }
}
