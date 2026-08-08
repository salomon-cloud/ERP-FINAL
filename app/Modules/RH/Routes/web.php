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
| Los privilegios son los que siembra RolPrivilegioSeeder para el modulo `rh`:
| empleados, departamentos, puestos, asistencias, permisos (+aprobar), nomina
| (+procesar, +aplicar), contratos, organigrama (+administrar) y reportes, cada
| uno con ver/crear/editar/eliminar.
|
| Dos entidades NO tienen privilegio propio sembrado -- documentos y
| evaluaciones -- y por eso van bajo `rh.empleados.*`: las dos son parte del
| expediente. Cuando se puedan agregar `rh.documentos.*` y `rh.evaluaciones.*`
| al seeder (que vive en la raiz del proyecto, fuera de este modulo), aqui se
| cambian y nada mas.
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `php artisan route:cache`.
| Ver docs/PLANNING.md - "Naming Conventions" y el Apendice A.
|
*/

use App\Modules\Compartido\Controllers\TableroModuloController;
use App\Modules\RH\Controllers\AsistenciaController;
use App\Modules\RH\Controllers\ContratoController;
use App\Modules\RH\Controllers\DepartamentoController;
use App\Modules\RH\Controllers\DocumentoEmpleadoController;
use App\Modules\RH\Controllers\EmpleadoController;
use App\Modules\RH\Controllers\EvaluacionDesempenoController;
use App\Modules\RH\Controllers\NominaController;
use App\Modules\RH\Controllers\NominaCorridaController;
use App\Modules\RH\Controllers\NominaPeriodoController;
use App\Modules\RH\Controllers\OrganigramaController;
use App\Modules\RH\Controllers\PermisoController;
use App\Modules\RH\Controllers\PuestoController;
use Illuminate\Support\Facades\Route;

// Pagina de entrada provisional del modulo. Sustituyela por el TableroController
// propio (Controllers/TableroController.php) conservando el nombre de ruta
// "rh.dashboard": la barra lateral apunta ahi.
Route::get('/', TableroModuloController::class)->name('dashboard');

/*
 * Catalogos: los datos maestros de la organizacion.
 */
Route::resource('empleados', EmpleadoController::class)
    ->middleware('permission:rh.empleados.ver');

Route::resource('departamentos', DepartamentoController::class)
    ->middleware('permission:rh.departamentos.ver');

Route::resource('puestos', PuestoController::class)
    ->middleware('permission:rh.puestos.ver');

Route::get('organigrama', OrganigramaController::class)
    ->name('organigrama')
    ->middleware('permission:rh.organigrama.ver');

/*
 * Control de tiempo.
 */
Route::resource('asistencias', AsistenciaController::class)
    ->except('show')
    ->middleware('permission:rh.asistencias.ver');

Route::resource('permisos', PermisoController::class)
    ->middleware('permission:rh.permisos.ver');

// Aprobar y rechazar son la misma decision, y por eso una sola ruta: el estado
// que viaja en el cuerpo dice cual de las dos es, y RevisarPermisoRequest se
// encarga de que solo pueda ser una de esas dos.
Route::patch('permisos/{permiso}/revisar', [PermisoController::class, 'revisar'])
    ->name('permisos.revisar')
    ->middleware('permission:rh.permisos.aprobar');

/*
 * Nomina, en sus tres niveles: periodo -> corrida -> recibo.
 */
Route::resource('nomina-periodos', NominaPeriodoController::class)
    ->parameters(['nomina-periodos' => 'nomina_periodo'])
    ->middleware('permission:rh.nomina.ver');

Route::resource('nomina-corridas', NominaCorridaController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy'])
    ->parameters(['nomina-corridas' => 'nomina_corrida'])
    ->middleware('permission:rh.nomina.ver');

Route::post('nomina-corridas/{nomina_corrida}/procesar', [NominaCorridaController::class, 'procesar'])
    ->name('nomina-corridas.procesar')
    ->middleware('permission:rh.nomina.procesar');

Route::post('nomina-corridas/{nomina_corrida}/aplicar', [NominaCorridaController::class, 'aplicar'])
    ->name('nomina-corridas.aplicar')
    ->middleware('permission:rh.nomina.aplicar');

Route::resource('nominas', NominaController::class)
    ->middleware('permission:rh.nomina.ver');

Route::patch('nominas/{nomina}/pagar', [NominaController::class, 'pagar'])
    ->name('nominas.pagar')
    ->middleware('permission:rh.nomina.editar');

/*
 * Expediente: contratos, documentos y evaluaciones.
 */
Route::resource('contratos', ContratoController::class)
    ->middleware('permission:rh.contratos.ver');

Route::resource('documentos', DocumentoEmpleadoController::class)
    ->except('show')
    ->parameters(['documentos' => 'documento'])
    ->middleware('permission:rh.empleados.ver');

Route::resource('evaluaciones', EvaluacionDesempenoController::class)
    ->parameters(['evaluaciones' => 'evaluacion'])
    ->middleware('permission:rh.empleados.ver');
