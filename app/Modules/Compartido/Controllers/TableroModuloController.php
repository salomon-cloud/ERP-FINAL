<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\RegistroMenu;
use App\Providers\ModuleServiceProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La pagina de entrada que cada modulo recibe gratis mientras no escribe su
 * propio tablero. Demuestra el cableado de punta a punta -- ruteo, namespace de
 * vistas, layout, menu -- y le dice al equipo exactamente por donde seguir.
 *
 * Cada equipo la sustituye por Controllers/TableroController.php conservando el
 * nombre de ruta "<modulo>.dashboard".
 */
class TableroModuloController extends Controller
{
    public function __invoke(Request $peticion): View
    {
        // El nombre de la ruta es "<slug>.dashboard"; el slug identifica al modulo.
        $slug = explode('.', (string) $peticion->route()?->getName())[0];
        $metadatos = RegistroMenu::metadatosPorSlug($slug);
        $modulo = $metadatos['modulo'];

        $ruta = ModuleServiceProvider::modulos()[$modulo] ?? app_path('Modules/'.$modulo);

        return view('compartido::paginas.modulo-pendiente', [
            'modulo' => $modulo,
            'slug' => $slug,
            'metadatos' => $metadatos,
            'migraciones' => count(glob($ruta.'/Migrations/*.php') ?: []),
            'readme' => 'app/Modules/'.$modulo.'/README.md',
        ]);
    }
}
