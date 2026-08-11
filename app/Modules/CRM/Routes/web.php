<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CRM - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra automaticamente App\Providers\ModuleServiceProvider bajo el
| prefijo de URL "/crm" y el prefijo de nombre "crm.".
| Los middleware 'web' y 'auth' YA estan aplicados a este grupo: no los repitas.
|
| Los nombres siguen modulo.recurso.accion:
|
|     Route::resource('pedidos', PedidoController::class)
|         ->middleware('permission:crm.pedidos.ver');   // crm.pedidos.index, ...
|
| Las acciones propias de un documento son verbos sobre el recurso:
|
|     Route::post('pedidos/{pedido}/confirmar', [PedidoController::class, 'confirmar'])
|         ->name('pedidos.confirmar')
|         ->middleware('permission:crm.pedidos.confirmar');
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `php artisan route:cache`.
| Ver docs/PLANNING.md - "Naming Conventions" y el Apendice A.7.
|
*/

use App\Modules\Compartido\Controllers\TableroModuloController;
use App\Modules\Compartido\Support\RegistroMenu;
use App\Modules\CRM\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

// Pagina de entrada provisional del modulo. Sustituyela por el TableroController
// propio (Controllers/TableroController.php) conservando el nombre de ruta
// "crm.dashboard": la barra lateral apunta ahi.
Route::get('/', TableroModuloController::class)
    ->name('dashboard')
    ->middleware('permission:ventas.clientes.ver');

/*
 * Entradas de la barra lateral. La banda del modulo CRM es la 60.
 *
 * CUIDADO: con `route:cache` este archivo no se carga y el modulo pierde estas
 * entradas (conserva la del tablero, que registra el cascaron) -- docs/david.md P6.
 */
RegistroMenu::registrar('CRM', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'crm.dashboard', 'privilegio' => 'ventas.clientes.ver', 'orden' => 60.01],
    ['etiqueta' => 'Clientes', 'icono' => 'people', 'ruta' => 'crm.clientes.index', 'privilegio' => 'ventas.clientes.ver', 'orden' => 60.02],
]);

/*
 * Clientes: el catalogo es de Ventas y aqui solo se lee. Se usa el privilegio de
 * lectura del catalogo (ventas.clientes.ver) en vez de sembrar uno nuevo, porque
 * CRM no es dueno del dato.
 */
Route::resource('clientes', ClienteController::class)
    ->only(['index', 'show'])
    ->middleware('permission:ventas.clientes.ver');
