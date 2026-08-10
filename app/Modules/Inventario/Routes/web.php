<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Inventario - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra automaticamente App\Providers\ModuleServiceProvider bajo el
| prefijo de URL "/inventario" y el prefijo de nombre "inventario.".
| Los middleware 'web' y 'auth' YA estan aplicados a este grupo: no los repitas.
|
| DOBLE FRENO DE PRIVILEGIOS (docs/david.md §20): el listado y la ficha piden
| el privilegio amplio de lectura (`...ver`), y cada accion de ciclo de vida
| pide ademas el suyo (`aprobar`, `cerrar`). Ver un ajuste no es autorizarlo.
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `php artisan route:cache`.
|
*/

use App\Modules\Compartido\Support\RegistroMenu;
use App\Modules\Inventario\Controllers\AjusteController;
use App\Modules\Inventario\Controllers\AlmacenController;
use App\Modules\Inventario\Controllers\CategoriaController;
use App\Modules\Inventario\Controllers\ConteoController;
use App\Modules\Inventario\Controllers\ExistenciaController;
use App\Modules\Inventario\Controllers\LoteController;
use App\Modules\Inventario\Controllers\MovimientoController;
use App\Modules\Inventario\Controllers\ProductoController;
use App\Modules\Inventario\Controllers\ReglaReordenController;
use App\Modules\Inventario\Controllers\ReporteController;
use App\Modules\Inventario\Controllers\TableroController;
use App\Modules\Inventario\Controllers\TraspasoController;
use App\Modules\Inventario\Controllers\UbicacionController;
use App\Modules\Inventario\Controllers\UnidadController;
use Illuminate\Support\Facades\Route;

/*
 * El tablero. Conserva el nombre "inventario.dashboard" porque es al que apunta
 * la entrada que el cascaron auto-registra en la barra lateral.
 *
 * Pide privilegio -- basta cualquiera de lectura del modulo -- porque pinta
 * datos reales. Ese enlace auto-registrado NO lleva privilegio, asi que un
 * usuario sin acceso lo seguira viendo y recibira un 403; emparejarlo exige
 * tocar ModuleServiceProvider, que vive fuera de este modulo (docs/david.md P5).
 */
Route::get('/', TableroController::class)
    ->name('dashboard')
    ->middleware('permission:inventario.productos.ver,inventario.existencias.ver,inventario.movimientos.ver');

/*
 * Entradas de la barra lateral.
 *
 * El orden va con decimales dentro de la banda del modulo (Inventario = 40):
 * con enteros no caben doce entradas entre 40 y 50 sin invadir al vecino.
 *
 * CUIDADO: este archivo es el unico gancho de arranque del modulo y
 * ModuleServiceProvider no lo carga con las rutas cacheadas
 * (`php artisan route:cache`). En ese caso el modulo conserva su tablero pero
 * pierde estas entradas. La solucion definitiva es un InventarioServiceProvider
 * registrado en bootstrap/providers.php -- fuera de este modulo (docs/david.md P6).
 */
RegistroMenu::registrar('Inventario', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'inventario.dashboard', 'privilegio' => 'inventario.productos.ver', 'orden' => 40.01],
    ['etiqueta' => 'Productos', 'icono' => 'box-seam', 'ruta' => 'inventario.productos.index', 'privilegio' => 'inventario.productos.ver', 'orden' => 40.02],
    ['etiqueta' => 'Categorias', 'icono' => 'diagram-2', 'ruta' => 'inventario.categorias.index', 'privilegio' => 'inventario.productos.ver', 'orden' => 40.03],
    ['etiqueta' => 'Unidades', 'icono' => 'rulers', 'ruta' => 'inventario.unidades.index', 'privilegio' => 'inventario.productos.ver', 'orden' => 40.04],
    ['etiqueta' => 'Almacenes', 'icono' => 'building', 'ruta' => 'inventario.almacenes.index', 'privilegio' => 'inventario.almacenes.ver', 'orden' => 40.05],
    ['etiqueta' => 'Ubicaciones', 'icono' => 'geo-alt', 'ruta' => 'inventario.ubicaciones.index', 'privilegio' => 'inventario.almacenes.ver', 'orden' => 40.06],
    ['etiqueta' => 'Existencias', 'icono' => 'clipboard-data', 'ruta' => 'inventario.existencias.index', 'privilegio' => 'inventario.existencias.ver', 'orden' => 40.07],
    ['etiqueta' => 'Kardex', 'icono' => 'journal-text', 'ruta' => 'inventario.movimientos.index', 'privilegio' => 'inventario.movimientos.ver', 'orden' => 40.08],
    ['etiqueta' => 'Traspasos', 'icono' => 'arrow-left-right', 'ruta' => 'inventario.traspasos.index', 'privilegio' => 'inventario.traspasos.ver', 'orden' => 40.09],
    ['etiqueta' => 'Ajustes', 'icono' => 'sliders', 'ruta' => 'inventario.ajustes.index', 'privilegio' => 'inventario.ajustes.ver', 'orden' => 40.10],
    ['etiqueta' => 'Conteos', 'icono' => 'ui-checks', 'ruta' => 'inventario.conteos.index', 'privilegio' => 'inventario.conteos.ver', 'orden' => 40.11],
    ['etiqueta' => 'Minimos y maximos', 'icono' => 'bell', 'ruta' => 'inventario.reglas-reorden.index', 'privilegio' => 'inventario.existencias.ver', 'orden' => 40.12],
    ['etiqueta' => 'Reportes de inventario', 'icono' => 'bar-chart', 'ruta' => 'inventario.reportes.index', 'privilegio' => 'inventario.reportes.ver', 'orden' => 40.13],
]);

/*
 * Catalogo: un solo catalogo de productos con filtro por categoria, no una
 * pantalla por familia de articulos (docs/david.md D4).
 */
Route::resource('productos', ProductoController::class)
    ->middleware('permission:inventario.productos.ver');

// Lotes y codigos de barras se administran desde la ficha del producto.
Route::middleware('permission:inventario.productos.editar')->group(function (): void {
    Route::post('productos/{producto}/lotes', [LoteController::class, 'guardarLote'])->name('productos.lotes.store');
    Route::delete('productos/{producto}/lotes/{lote}', [LoteController::class, 'eliminarLote'])->name('productos.lotes.destroy');
    Route::post('productos/{producto}/codigos', [LoteController::class, 'guardarCodigo'])->name('productos.codigos.store');
    Route::delete('productos/{producto}/codigos/{codigo}', [LoteController::class, 'eliminarCodigo'])->name('productos.codigos.destroy');
});

Route::resource('categorias', CategoriaController::class)
    ->except('show')
    ->parameters(['categorias' => 'categoria'])
    ->middleware('permission:inventario.productos.ver');

Route::resource('unidades', UnidadController::class)
    ->except('show')
    ->parameters(['unidades' => 'unidad'])
    ->middleware('permission:inventario.productos.ver');

/*
 * Almacenes y ubicaciones.
 */
Route::resource('almacenes', AlmacenController::class)
    ->parameters(['almacenes' => 'almacen'])
    ->middleware('permission:inventario.almacenes.ver');

Route::resource('ubicaciones', UbicacionController::class)
    ->except('show')
    ->parameters(['ubicaciones' => 'ubicacion'])
    ->middleware('permission:inventario.almacenes.ver');

/*
 * Consulta: existencias y kardex. Las dos son de solo lectura -- la existencia
 * no se edita, se mueve.
 */
Route::get('existencias', [ExistenciaController::class, 'index'])
    ->name('existencias.index')
    ->middleware('permission:inventario.existencias.ver');

Route::get('movimientos', [MovimientoController::class, 'index'])
    ->name('movimientos.index')
    ->middleware('permission:inventario.movimientos.ver');

Route::resource('reglas-reorden', ReglaReordenController::class)
    ->except('show')
    ->parameters(['reglas-reorden' => 'regla'])
    ->middleware('permission:inventario.existencias.ver');

/*
 * Traspasos: enviar descarga el origen, recibir carga el destino.
 */
Route::resource('traspasos', TraspasoController::class)
    ->middleware('permission:inventario.traspasos.ver');

Route::post('traspasos/{traspaso}/lineas', [TraspasoController::class, 'agregarLinea'])
    ->name('traspasos.lineas.store')
    ->middleware('permission:inventario.traspasos.editar');

Route::delete('traspasos/{traspaso}/lineas/{linea}', [TraspasoController::class, 'eliminarLinea'])
    ->name('traspasos.lineas.destroy')
    ->middleware('permission:inventario.traspasos.editar');

Route::post('traspasos/{traspaso}/enviar', [TraspasoController::class, 'enviar'])
    ->name('traspasos.enviar')
    ->middleware('permission:inventario.traspasos.aprobar');

Route::post('traspasos/{traspaso}/recibir', [TraspasoController::class, 'recibir'])
    ->name('traspasos.recibir')
    ->middleware('permission:inventario.traspasos.aprobar');

Route::post('traspasos/{traspaso}/cancelar', [TraspasoController::class, 'cancelar'])
    ->name('traspasos.cancelar')
    ->middleware('permission:inventario.traspasos.editar');

/*
 * Ajustes: capturar y autorizar son dos privilegios distintos.
 */
Route::resource('ajustes', AjusteController::class)
    ->middleware('permission:inventario.ajustes.ver');

Route::post('ajustes/{ajuste}/lineas', [AjusteController::class, 'agregarLinea'])
    ->name('ajustes.lineas.store')
    ->middleware('permission:inventario.ajustes.editar');

Route::delete('ajustes/{ajuste}/lineas/{linea}', [AjusteController::class, 'eliminarLinea'])
    ->name('ajustes.lineas.destroy')
    ->middleware('permission:inventario.ajustes.editar');

Route::post('ajustes/{ajuste}/aplicar', [AjusteController::class, 'aplicar'])
    ->name('ajustes.aplicar')
    ->middleware('permission:inventario.ajustes.aprobar');

Route::post('ajustes/{ajuste}/cancelar', [AjusteController::class, 'cancelar'])
    ->name('ajustes.cancelar')
    ->middleware('permission:inventario.ajustes.editar');

/*
 * Conteos fisicos.
 */
Route::resource('conteos', ConteoController::class)
    ->middleware('permission:inventario.conteos.ver');

Route::post('conteos/{conteo}/iniciar', [ConteoController::class, 'iniciar'])
    ->name('conteos.iniciar')
    ->middleware('permission:inventario.conteos.editar');

Route::post('conteos/{conteo}/capturar', [ConteoController::class, 'capturar'])
    ->name('conteos.capturar')
    ->middleware('permission:inventario.conteos.editar');

Route::post('conteos/{conteo}/productos', [ConteoController::class, 'agregarProducto'])
    ->name('conteos.productos.store')
    ->middleware('permission:inventario.conteos.editar');

// Cerrar es lo que toca el inventario, y por eso tiene su propio privilegio.
Route::post('conteos/{conteo}/cerrar', [ConteoController::class, 'cerrar'])
    ->name('conteos.cerrar')
    ->middleware('permission:inventario.conteos.cerrar');

/*
 * Reportes. Todos de solo lectura y todos con ?formato=csv.
 */
Route::middleware('permission:inventario.reportes.ver')->group(function (): void {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/existencias', [ReporteController::class, 'existencias'])->name('reportes.existencias');
    Route::get('reportes/movimientos', [ReporteController::class, 'movimientos'])->name('reportes.movimientos');
    Route::get('reportes/stock-bajo', [ReporteController::class, 'stockBajo'])->name('reportes.stock-bajo');
    Route::get('reportes/valoracion', [ReporteController::class, 'valoracion'])->name('reportes.valoracion');
    Route::get('reportes/caducidades', [ReporteController::class, 'caducidades'])->name('reportes.caducidades');
});
