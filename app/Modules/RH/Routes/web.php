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

use App\Modules\Compartido\Support\RegistroMenu;
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
use App\Modules\RH\Controllers\TableroController;
use Illuminate\Support\Facades\Route;

// El tablero del modulo. Conserva el nombre "rh.dashboard" porque es al que
// apunta la barra lateral.
//
// A diferencia de la pagina provisional que sustituye, este tablero pinta datos
// reales -- plantilla, ausencias del dia, nomina, cumpleanos -- asi que exige
// privilegio. Basta con cualquiera de lectura del modulo.
//
// Nota: la entrada de la barra lateral que el cascaron registra sola para cada
// modulo NO lleva privilegio, asi que un usuario sin acceso a RH seguira viendo
// el enlace y recibira un 403 al entrar. Emparejarlo exige que
// ModuleServiceProvider pase un privilegio al auto-registrar, y ese archivo
// vive fuera de este modulo.
Route::get('/', TableroController::class)
    ->name('dashboard')
    ->middleware('permission:rh.empleados.ver,rh.asistencias.ver,rh.permisos.ver,rh.nomina.ver');

/*
 * Entradas de la barra lateral.
 *
 * El cascaron ya registra solo la del tablero; estas son las de RH. Las que
 * apuntan a una ruta inexistente o a un privilegio que el usuario no tiene
 * nunca se pintan, de eso se encarga RegistroMenu::visiblesPara().
 *
 * CUIDADO: este archivo es el unico gancho de arranque que tiene un modulo, y
 * ModuleServiceProvider no lo carga cuando las rutas estan cacheadas
 * (`php artisan route:cache`). En ese caso el modulo conserva su entrada de
 * tablero pero pierde estas. La solucion definitiva es un
 * Providers/RHServiceProvider, que exige registrarlo en bootstrap/providers.php
 * -- fuera de este modulo. Ver PLAN_IMPLEMENTACION.md.
 */
RegistroMenu::registrar('RH', [
    ['etiqueta' => 'Empleados', 'icono' => 'people', 'ruta' => 'rh.empleados.index', 'privilegio' => 'rh.empleados.ver', 'orden' => 51],
    ['etiqueta' => 'Departamentos', 'icono' => 'building', 'ruta' => 'rh.departamentos.index', 'privilegio' => 'rh.departamentos.ver', 'orden' => 52],
    ['etiqueta' => 'Puestos', 'icono' => 'briefcase', 'ruta' => 'rh.puestos.index', 'privilegio' => 'rh.puestos.ver', 'orden' => 53],
    ['etiqueta' => 'Organigrama', 'icono' => 'diagram-3', 'ruta' => 'rh.organigrama', 'privilegio' => 'rh.organigrama.ver', 'orden' => 54],
    ['etiqueta' => 'Asistencias', 'icono' => 'calendar-check', 'ruta' => 'rh.asistencias.index', 'privilegio' => 'rh.asistencias.ver', 'orden' => 55],
    ['etiqueta' => 'Permisos', 'icono' => 'calendar2-week', 'ruta' => 'rh.permisos.index', 'privilegio' => 'rh.permisos.ver', 'orden' => 56],
    ['etiqueta' => 'Periodos de nomina', 'icono' => 'calendar3', 'ruta' => 'rh.nomina-periodos.index', 'privilegio' => 'rh.nomina.ver', 'orden' => 57],
    ['etiqueta' => 'Corridas de nomina', 'icono' => 'cash-stack', 'ruta' => 'rh.nomina-corridas.index', 'privilegio' => 'rh.nomina.ver', 'orden' => 58],
    ['etiqueta' => 'Recibos', 'icono' => 'receipt', 'ruta' => 'rh.nominas.index', 'privilegio' => 'rh.nomina.ver', 'orden' => 59],
    ['etiqueta' => 'Contratos', 'icono' => 'file-earmark-text', 'ruta' => 'rh.contratos.index', 'privilegio' => 'rh.contratos.ver', 'orden' => 60],
    ['etiqueta' => 'Documentos', 'icono' => 'folder2-open', 'ruta' => 'rh.documentos.index', 'privilegio' => 'rh.empleados.ver', 'orden' => 61],
    ['etiqueta' => 'Evaluaciones', 'icono' => 'clipboard-check', 'ruta' => 'rh.evaluaciones.index', 'privilegio' => 'rh.empleados.ver', 'orden' => 62],
]);

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
