<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Compras - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra App\Providers\ModuleServiceProvider bajo el prefijo de URL
| "/compras" y el prefijo de nombre "compras.".
| Los middleware 'web' y 'auth' YA estan aplicados: no los repitas.
|
| DOBLE FRENO DE PRIVILEGIOS: el listado y la ficha piden el privilegio amplio
| de lectura; cada accion de ciclo de vida pide el suyo. Ver una requisicion no
| es aprobarla, y ver una recepcion no es aplicarla.
|
| Los privilegios son los que siembra RolPrivilegioSeeder para `compras`:
| proveedores, requisiciones (+aprobar), ordenes (+confirmar, +cancelar),
| recepciones (+aplicar), facturas (+contabilizar), pagos (+aplicar) y reportes.
|
| Las DEVOLUCIONES no tienen privilegio propio sembrado y van bajo
| `compras.facturas.*`: una devolucion es un ajuste a una factura ya emitida.
| El dia que se agregue `compras.devoluciones.*` al seeder -- que vive en la
| raiz del proyecto, fuera de este modulo -- aqui se cambia y nada mas.
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `route:cache`.
|
*/

use App\Modules\Compartido\Support\RegistroMenu;
use App\Modules\Compras\Controllers\DevolucionCompraController;
use App\Modules\Compras\Controllers\FacturaProveedorController;
use App\Modules\Compras\Controllers\OrdenCompraController;
use App\Modules\Compras\Controllers\PagoController;
use App\Modules\Compras\Controllers\ProveedorController;
use App\Modules\Compras\Controllers\RecepcionController;
use App\Modules\Compras\Controllers\ReporteController;
use App\Modules\Compras\Controllers\RequisicionController;
use App\Modules\Compras\Controllers\TableroController;
use Illuminate\Support\Facades\Route;

Route::get('/', TableroController::class)
    ->name('dashboard')
    ->middleware('permission:compras.proveedores.ver,compras.ordenes.ver,compras.requisiciones.ver');

/*
 * Entradas de la barra lateral. El orden va con decimales dentro de la banda
 * del modulo (Compras = 30) para que las ocho quepan sin invadir a Inventario.
 *
 * CUIDADO: con `route:cache` este archivo no se carga y el modulo pierde estas
 * entradas (conserva la del tablero, que registra el cascaron). Ver el mismo
 * aviso en RH y en Inventario -- docs/david.md P6.
 */
RegistroMenu::registrar('Compras', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'compras.dashboard', 'privilegio' => 'compras.ordenes.ver', 'orden' => 30.01],
    ['etiqueta' => 'Proveedores', 'icono' => 'shop', 'ruta' => 'compras.proveedores.index', 'privilegio' => 'compras.proveedores.ver', 'orden' => 30.02],
    ['etiqueta' => 'Requisiciones', 'icono' => 'clipboard-plus', 'ruta' => 'compras.requisiciones.index', 'privilegio' => 'compras.requisiciones.ver', 'orden' => 30.03],
    ['etiqueta' => 'Ordenes de compra', 'icono' => 'file-earmark-text', 'ruta' => 'compras.ordenes.index', 'privilegio' => 'compras.ordenes.ver', 'orden' => 30.04],
    ['etiqueta' => 'Recepciones', 'icono' => 'box-arrow-in-down', 'ruta' => 'compras.recepciones.index', 'privilegio' => 'compras.recepciones.ver', 'orden' => 30.05],
    ['etiqueta' => 'Facturas de proveedor', 'icono' => 'receipt', 'ruta' => 'compras.facturas.index', 'privilegio' => 'compras.facturas.ver', 'orden' => 30.06],
    ['etiqueta' => 'Devoluciones', 'icono' => 'arrow-return-left', 'ruta' => 'compras.devoluciones.index', 'privilegio' => 'compras.facturas.ver', 'orden' => 30.07],
    ['etiqueta' => 'Pagos', 'icono' => 'cash-stack', 'ruta' => 'compras.pagos.index', 'privilegio' => 'compras.pagos.ver', 'orden' => 30.08],
    ['etiqueta' => 'Reportes de compras', 'icono' => 'bar-chart', 'ruta' => 'compras.reportes.index', 'privilegio' => 'compras.reportes.ver', 'orden' => 30.09],
]);

/*
 * Catalogo de proveedores.
 */
Route::resource('proveedores', ProveedorController::class)
    ->parameters(['proveedores' => 'proveedor'])
    ->middleware('permission:compras.proveedores.ver');

/*
 * Requisiciones: la peticion interna. Aprobar y rechazar son la MISMA ruta,
 * porque son la misma decision (igual que revisar un permiso en RH).
 */
Route::resource('requisiciones', RequisicionController::class)
    ->parameters(['requisiciones' => 'requisicion'])
    ->middleware('permission:compras.requisiciones.ver');

Route::post('requisiciones/{requisicion}/lineas', [RequisicionController::class, 'agregarLinea'])
    ->name('requisiciones.lineas.store')
    ->middleware('permission:compras.requisiciones.editar');

Route::delete('requisiciones/{requisicion}/lineas/{linea}', [RequisicionController::class, 'eliminarLinea'])
    ->name('requisiciones.lineas.destroy')
    ->middleware('permission:compras.requisiciones.editar');

Route::post('requisiciones/{requisicion}/enviar', [RequisicionController::class, 'enviar'])
    ->name('requisiciones.enviar')
    ->middleware('permission:compras.requisiciones.editar');

Route::patch('requisiciones/{requisicion}/revisar', [RequisicionController::class, 'revisar'])
    ->name('requisiciones.revisar')
    ->middleware('permission:compras.requisiciones.aprobar');

Route::post('requisiciones/{requisicion}/convertir', [RequisicionController::class, 'convertir'])
    ->name('requisiciones.convertir')
    ->middleware('permission:compras.ordenes.crear');

Route::post('requisiciones/{requisicion}/cerrar', [RequisicionController::class, 'cerrar'])
    ->name('requisiciones.cerrar')
    ->middleware('permission:compras.requisiciones.editar');

/*
 * Ordenes de compra.
 */
Route::resource('ordenes', OrdenCompraController::class)
    ->parameters(['ordenes' => 'orden'])
    ->middleware('permission:compras.ordenes.ver');

Route::post('ordenes/{orden}/lineas', [OrdenCompraController::class, 'agregarLinea'])
    ->name('ordenes.lineas.store')
    ->middleware('permission:compras.ordenes.editar');

Route::delete('ordenes/{orden}/lineas/{linea}', [OrdenCompraController::class, 'eliminarLinea'])
    ->name('ordenes.lineas.destroy')
    ->middleware('permission:compras.ordenes.editar');

Route::post('ordenes/{orden}/enviar', [OrdenCompraController::class, 'enviar'])
    ->name('ordenes.enviar')
    ->middleware('permission:compras.ordenes.editar');

Route::post('ordenes/{orden}/confirmar', [OrdenCompraController::class, 'confirmar'])
    ->name('ordenes.confirmar')
    ->middleware('permission:compras.ordenes.confirmar');

Route::post('ordenes/{orden}/cancelar', [OrdenCompraController::class, 'cancelar'])
    ->name('ordenes.cancelar')
    ->middleware('permission:compras.ordenes.cancelar');

/*
 * Recepciones: aplicar es lo que crea inventario, y por eso tiene su privilegio.
 * No hay edit/update: una recepcion se corrige con sus lineas o se cancela.
 */
Route::resource('recepciones', RecepcionController::class)
    ->only(['index', 'create', 'store', 'show'])
    ->parameters(['recepciones' => 'recepcion'])
    ->middleware('permission:compras.recepciones.ver');

Route::post('recepciones/{recepcion}/lineas', [RecepcionController::class, 'agregarLinea'])
    ->name('recepciones.lineas.store')
    ->middleware('permission:compras.recepciones.editar');

Route::delete('recepciones/{recepcion}/lineas/{linea}', [RecepcionController::class, 'eliminarLinea'])
    ->name('recepciones.lineas.destroy')
    ->middleware('permission:compras.recepciones.editar');

Route::post('recepciones/{recepcion}/aplicar', [RecepcionController::class, 'aplicar'])
    ->name('recepciones.aplicar')
    ->middleware('permission:compras.recepciones.aplicar');

Route::post('recepciones/{recepcion}/cancelar', [RecepcionController::class, 'cancelar'])
    ->name('recepciones.cancelar')
    ->middleware('permission:compras.recepciones.aplicar');

/*
 * Facturas de proveedor y su cotejo de tres vias.
 */
Route::resource('facturas', FacturaProveedorController::class)
    ->parameters(['facturas' => 'factura'])
    ->middleware('permission:compras.facturas.ver');

Route::post('facturas/{factura}/lineas', [FacturaProveedorController::class, 'agregarLinea'])
    ->name('facturas.lineas.store')
    ->middleware('permission:compras.facturas.editar');

Route::delete('facturas/{factura}/lineas/{linea}', [FacturaProveedorController::class, 'eliminarLinea'])
    ->name('facturas.lineas.destroy')
    ->middleware('permission:compras.facturas.editar');

Route::post('facturas/{factura}/copiar-orden', [FacturaProveedorController::class, 'copiarDeOrden'])
    ->name('facturas.copiar-orden')
    ->middleware('permission:compras.facturas.editar');

Route::post('facturas/{factura}/contabilizar', [FacturaProveedorController::class, 'contabilizar'])
    ->name('facturas.contabilizar')
    ->middleware('permission:compras.facturas.contabilizar');

Route::post('facturas/{factura}/cancelar', [FacturaProveedorController::class, 'cancelar'])
    ->name('facturas.cancelar')
    ->middleware('permission:compras.facturas.contabilizar');

/*
 * Devoluciones a proveedor. Sin privilegio propio sembrado: van bajo facturas.
 */
Route::resource('devoluciones', DevolucionCompraController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy'])
    ->parameters(['devoluciones' => 'devolucion'])
    ->middleware('permission:compras.facturas.ver');

Route::post('devoluciones/{devolucion}/lineas', [DevolucionCompraController::class, 'agregarLinea'])
    ->name('devoluciones.lineas.store')
    ->middleware('permission:compras.facturas.editar');

Route::delete('devoluciones/{devolucion}/lineas/{linea}', [DevolucionCompraController::class, 'eliminarLinea'])
    ->name('devoluciones.lineas.destroy')
    ->middleware('permission:compras.facturas.editar');

Route::post('devoluciones/{devolucion}/aplicar', [DevolucionCompraController::class, 'aplicar'])
    ->name('devoluciones.aplicar')
    ->middleware('permission:compras.facturas.contabilizar');

Route::post('devoluciones/{devolucion}/cancelar', [DevolucionCompraController::class, 'cancelar'])
    ->name('devoluciones.cancelar')
    ->middleware('permission:compras.facturas.contabilizar');

/*
 * Pagos a proveedores.
 */
Route::resource('pagos', PagoController::class)
    ->parameters(['pagos' => 'pago'])
    ->middleware('permission:compras.pagos.ver');

Route::post('pagos/{pago}/aplicar', [PagoController::class, 'aplicar'])
    ->name('pagos.aplicar')
    ->middleware('permission:compras.pagos.aplicar');

Route::post('pagos/{pago}/cancelar', [PagoController::class, 'cancelar'])
    ->name('pagos.cancelar')
    ->middleware('permission:compras.pagos.aplicar');

/*
 * Reportes. Todos de solo lectura y todos con ?formato=csv.
 */
Route::middleware('permission:compras.reportes.ver')->group(function (): void {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/por-periodo', [ReporteController::class, 'porPeriodo'])->name('reportes.por-periodo');
    Route::get('reportes/por-proveedor', [ReporteController::class, 'porProveedor'])->name('reportes.por-proveedor');
    Route::get('reportes/por-producto', [ReporteController::class, 'porProducto'])->name('reportes.por-producto');
    Route::get('reportes/pendientes-por-recibir', [ReporteController::class, 'pendientesPorRecibir'])->name('reportes.pendientes-por-recibir');
    Route::get('reportes/cuentas-por-pagar', [ReporteController::class, 'cuentasPorPagar'])->name('reportes.cuentas-por-pagar');
});
