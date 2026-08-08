<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Recursos Humanos - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra automaticamente App\Providers\ModuleServiceProvider bajo el
| prefijo de URL "/rh" y el prefijo de nombre "rh.".
| Los middleware 'web' y 'auth' YA estan aplicados a este grupo: no los repitas.
|
| Los nombres siguen modulo.recurso.accion:
|
|     Route::resource('pedidos', PedidoController::class)
|         ->middleware('permission:rh.pedidos.ver');   // rh.pedidos.index, ...
|
| Las acciones propias de un documento son verbos sobre el recurso:
|
|     Route::post('pedidos/{pedido}/confirmar', [PedidoController::class, 'confirmar'])
|         ->name('pedidos.confirmar')
|         ->middleware('permission:rh.pedidos.confirmar');
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `php artisan route:cache`.
| Ver docs/PLANNING.md - "Naming Conventions" y el Apendice A.7.
|
*/

use App\Modules\Compartido\Controllers\TableroModuloController;
use Illuminate\Support\Facades\Route;

// Pagina de entrada provisional del modulo. Sustituyela por el TableroController
// propio (Controllers/TableroController.php) conservando el nombre de ruta
// "rh.dashboard": la barra lateral apunta ahi.
Route::get('/', TableroModuloController::class)->name('dashboard');
