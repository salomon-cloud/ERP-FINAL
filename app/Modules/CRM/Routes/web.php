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
use App\Modules\CRM\Controllers\ActividadController;
use App\Modules\CRM\Controllers\ContactoController;
use App\Modules\CRM\Controllers\EmpresaController;
use App\Modules\CRM\Controllers\OportunidadController;
use App\Modules\CRM\Controllers\ProspectoController;
use App\Modules\CRM\Controllers\ReporteController;
use App\Modules\CRM\Controllers\TableroController;
use App\Modules\CRM\Controllers\TareaController;
use App\Modules\CRM\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

// Tablero propio del CRM. Conserva el nombre de ruta "crm.dashboard" porque
// es el que usa el layout y el auto-registro de menu.
Route::get('/', TableroController::class)
    ->name('dashboard')
    ->middleware('permission:crm.prospectos.ver,crm.oportunidades.ver,crm.contactos.ver,crm.actividades.ver');

/*
 * Entradas de la barra lateral. La banda del modulo CRM es la 60.
 *
 * CUIDADO: con `route:cache` este archivo no se carga y el modulo pierde estas
 * entradas (conserva la del tablero, que registra el cascaron) -- docs/david.md P6.
 */
RegistroMenu::registrar('CRM', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'crm.dashboard', 'privilegio' => 'crm.prospectos.ver', 'orden' => 60.01],
    ['etiqueta' => 'Empresas', 'icono' => 'buildings', 'ruta' => 'crm.empresas.index', 'privilegio' => 'crm.empresas.ver', 'orden' => 60.02],
    ['etiqueta' => 'Prospectos', 'icono' => 'person-badge', 'ruta' => 'crm.prospectos.index', 'privilegio' => 'crm.prospectos.ver', 'orden' => 60.03],
    ['etiqueta' => 'Contactos', 'icono' => 'people', 'ruta' => 'crm.contactos.index', 'privilegio' => 'crm.contactos.ver', 'orden' => 60.04],
    ['etiqueta' => 'Oportunidades', 'icono' => 'diagram-3', 'ruta' => 'crm.oportunidades.index', 'privilegio' => 'crm.oportunidades.ver', 'orden' => 60.05],
    ['etiqueta' => 'Actividades', 'icono' => 'calendar-check', 'ruta' => 'crm.actividades.index', 'privilegio' => 'crm.actividades.ver', 'orden' => 60.06],
    ['etiqueta' => 'Notas', 'icono' => 'stickies', 'ruta' => 'crm.notas.index', 'privilegio' => 'crm.notas.ver', 'orden' => 60.07],
    ['etiqueta' => 'Tareas', 'icono' => 'check2-square', 'ruta' => 'crm.tareas.index', 'privilegio' => 'crm.tareas.ver', 'orden' => 60.08],
    ['etiqueta' => 'Clientes', 'icono' => 'people', 'ruta' => 'crm.clientes.index', 'privilegio' => 'ventas.clientes.ver', 'orden' => 60.09],
    ['etiqueta' => 'Reportes', 'icono' => 'bar-chart', 'ruta' => 'crm.reportes.index', 'privilegio' => 'crm.reportes.ver', 'orden' => 60.10],
]);

/*
 * Clientes: el catalogo es de Ventas y aqui solo se lee. Se usa el privilegio de
 * lectura del catalogo (ventas.clientes.ver) en vez de sembrar uno nuevo, porque
 * CRM no es dueno del dato.
 */
Route::resource('clientes', ClienteController::class)
    ->only(['index', 'show'])
    ->middleware('permission:ventas.clientes.ver');

Route::resource('empresas', EmpresaController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.empresas.ver');

Route::resource('prospectos', ProspectoController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.prospectos.ver');

Route::resource('contactos', ContactoController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.contactos.ver');

Route::resource('oportunidades', OportunidadController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.oportunidades.ver');

Route::resource('actividades', ActividadController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.actividades.ver');

Route::resource('tareas', TareaController::class)
    ->only(['index', 'show'])
    ->middleware('permission:crm.tareas.ver');

Route::middleware('permission:crm.reportes.ver')->group(function (): void {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/embudo', [ReporteController::class, 'embudo'])->name('reportes.embudo');
    Route::get('reportes/prospectos', [ReporteController::class, 'prospectos'])->name('reportes.prospectos');
    Route::get('reportes/actividades', [ReporteController::class, 'actividades'])->name('reportes.actividades');
});
