<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Ventas - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra App\Providers\ModuleServiceProvider bajo el prefijo de URL
| "/ventas" y el prefijo de nombre "ventas.".
| Los middleware 'web' y 'auth' YA estan aplicados: no los repitas.
|
| DOBLE FRENO DE PRIVILEGIOS: el listado y la ficha piden el privilegio amplio
| de lectura; cada accion de ciclo de vida pide el suyo. Ver un pedido no es
| confirmarlo, y ver una factura no es emitirla.
|
| Los privilegios son los que siembra RolPrivilegioSeeder para `ventas`:
| clientes, cotizaciones (+convertir), pedidos (+confirmar, +cancelar),
| facturas (+emitir, +cancelar), notas_credito (+emitir), cobros (+aplicar),
| descuentos (+autorizar) y reportes.
|
| Las LISTAS DE PRECIOS no tienen privilegio propio sembrado y van bajo
| `ventas.clientes.*`: son parte de la relacion comercial con el cliente. El
| dia que se agregue `ventas.listas_precios.*` al seeder -- que vive en la raiz
| del proyecto, fuera de este modulo -- aqui se cambia y nada mas.
|
| Nunca uses un closure aqui: las rutas deben sobrevivir a `route:cache`.
|
*/

use App\Modules\Compartido\Support\RegistroMenu;
use App\Modules\Ventas\Controllers\ClienteController;
use App\Modules\Ventas\Controllers\CobroController;
use App\Modules\Ventas\Controllers\CotizacionController;
use App\Modules\Ventas\Controllers\FacturaController;
use App\Modules\Ventas\Controllers\ListaPrecioController;
use App\Modules\Ventas\Controllers\NotaCreditoController;
use App\Modules\Ventas\Controllers\PedidoController;
use App\Modules\Ventas\Controllers\ReporteController;
use App\Modules\Ventas\Controllers\TableroController;
use Illuminate\Support\Facades\Route;

Route::get('/', TableroController::class)
    ->name('dashboard')
    ->middleware('permission:ventas.clientes.ver,ventas.pedidos.ver,ventas.facturas.ver');

/*
 * Entradas de la barra lateral. El orden va con decimales dentro de la banda
 * del modulo (Ventas = 20).
 *
 * CUIDADO: con `route:cache` este archivo no se carga y el modulo pierde estas
 * entradas (conserva la del tablero, que registra el cascaron) -- docs/david.md P6.
 */
RegistroMenu::registrar('Ventas', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'ventas.dashboard', 'privilegio' => 'ventas.pedidos.ver', 'orden' => 20.01],
    ['etiqueta' => 'Clientes', 'icono' => 'people', 'ruta' => 'ventas.clientes.index', 'privilegio' => 'ventas.clientes.ver', 'orden' => 20.02],
    ['etiqueta' => 'Listas de precios', 'icono' => 'tags', 'ruta' => 'ventas.listas-precios.index', 'privilegio' => 'ventas.clientes.ver', 'orden' => 20.03],
    ['etiqueta' => 'Cotizaciones', 'icono' => 'file-earmark-text', 'ruta' => 'ventas.cotizaciones.index', 'privilegio' => 'ventas.cotizaciones.ver', 'orden' => 20.04],
    ['etiqueta' => 'Pedidos', 'icono' => 'cart-check', 'ruta' => 'ventas.pedidos.index', 'privilegio' => 'ventas.pedidos.ver', 'orden' => 20.05],
    ['etiqueta' => 'Facturas', 'icono' => 'receipt', 'ruta' => 'ventas.facturas.index', 'privilegio' => 'ventas.facturas.ver', 'orden' => 20.06],
    ['etiqueta' => 'Notas de credito', 'icono' => 'arrow-return-left', 'ruta' => 'ventas.notas-credito.index', 'privilegio' => 'ventas.notas_credito.ver', 'orden' => 20.07],
    ['etiqueta' => 'Cobros', 'icono' => 'cash-coin', 'ruta' => 'ventas.cobros.index', 'privilegio' => 'ventas.cobros.ver', 'orden' => 20.08],
    ['etiqueta' => 'Reportes de ventas', 'icono' => 'bar-chart', 'ruta' => 'ventas.reportes.index', 'privilegio' => 'ventas.reportes.ver', 'orden' => 20.09],
]);

/*
 * Catalogo de clientes y sus listas de precios.
 */
Route::resource('clientes', ClienteController::class)
    ->middleware('permission:ventas.clientes.ver');

Route::resource('listas-precios', ListaPrecioController::class)
    ->parameters(['listas-precios' => 'lista'])
    ->middleware('permission:ventas.clientes.ver');

Route::post('listas-precios/{lista}/items', [ListaPrecioController::class, 'agregarItem'])
    ->name('listas-precios.items.store')
    ->middleware('permission:ventas.clientes.editar');

Route::delete('listas-precios/{lista}/items/{item}', [ListaPrecioController::class, 'eliminarItem'])
    ->name('listas-precios.items.destroy')
    ->middleware('permission:ventas.clientes.editar');

/*
 * Cotizaciones. Aceptar y rechazar son la MISMA ruta: es una sola decision.
 */
Route::resource('cotizaciones', CotizacionController::class)
    ->parameters(['cotizaciones' => 'cotizacion'])
    ->middleware('permission:ventas.cotizaciones.ver');

Route::post('cotizaciones/{cotizacion}/lineas', [CotizacionController::class, 'agregarLinea'])
    ->name('cotizaciones.lineas.store')
    ->middleware('permission:ventas.cotizaciones.editar');

Route::delete('cotizaciones/{cotizacion}/lineas/{linea}', [CotizacionController::class, 'eliminarLinea'])
    ->name('cotizaciones.lineas.destroy')
    ->middleware('permission:ventas.cotizaciones.editar');

Route::post('cotizaciones/{cotizacion}/enviar', [CotizacionController::class, 'enviar'])
    ->name('cotizaciones.enviar')
    ->middleware('permission:ventas.cotizaciones.editar');

Route::patch('cotizaciones/{cotizacion}/responder', [CotizacionController::class, 'responder'])
    ->name('cotizaciones.responder')
    ->middleware('permission:ventas.cotizaciones.editar');

Route::post('cotizaciones/{cotizacion}/convertir', [CotizacionController::class, 'convertir'])
    ->name('cotizaciones.convertir')
    ->middleware('permission:ventas.cotizaciones.convertir');

Route::post('cotizaciones/{cotizacion}/cancelar', [CotizacionController::class, 'cancelar'])
    ->name('cotizaciones.cancelar')
    ->middleware('permission:ventas.cotizaciones.editar');

/*
 * Pedidos: confirmar aparta existencia, surtir la saca del almacen.
 */
Route::resource('pedidos', PedidoController::class)
    ->middleware('permission:ventas.pedidos.ver');

Route::post('pedidos/{pedido}/lineas', [PedidoController::class, 'agregarLinea'])
    ->name('pedidos.lineas.store')
    ->middleware('permission:ventas.pedidos.editar');

Route::delete('pedidos/{pedido}/lineas/{linea}', [PedidoController::class, 'eliminarLinea'])
    ->name('pedidos.lineas.destroy')
    ->middleware('permission:ventas.pedidos.editar');

Route::post('pedidos/{pedido}/confirmar', [PedidoController::class, 'confirmar'])
    ->name('pedidos.confirmar')
    ->middleware('permission:ventas.pedidos.confirmar');

// Surtir mueve inventario de verdad, y por eso pide el mismo privilegio que
// confirmar: no es una edicion cualquiera del pedido.
Route::post('pedidos/{pedido}/surtir', [PedidoController::class, 'surtir'])
    ->name('pedidos.surtir')
    ->middleware('permission:ventas.pedidos.confirmar');

Route::post('pedidos/{pedido}/facturar', [PedidoController::class, 'facturar'])
    ->name('pedidos.facturar')
    ->middleware('permission:ventas.facturas.crear');

Route::post('pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])
    ->name('pedidos.cancelar')
    ->middleware('permission:ventas.pedidos.cancelar');

/*
 * Facturas.
 */
Route::resource('facturas', FacturaController::class)
    ->parameters(['facturas' => 'factura'])
    ->middleware('permission:ventas.facturas.ver');

Route::post('facturas/{factura}/lineas', [FacturaController::class, 'agregarLinea'])
    ->name('facturas.lineas.store')
    ->middleware('permission:ventas.facturas.editar');

Route::delete('facturas/{factura}/lineas/{linea}', [FacturaController::class, 'eliminarLinea'])
    ->name('facturas.lineas.destroy')
    ->middleware('permission:ventas.facturas.editar');

Route::post('facturas/{factura}/emitir', [FacturaController::class, 'emitir'])
    ->name('facturas.emitir')
    ->middleware('permission:ventas.facturas.emitir');

Route::post('facturas/{factura}/cancelar', [FacturaController::class, 'cancelar'])
    ->name('facturas.cancelar')
    ->middleware('permission:ventas.facturas.cancelar');

/*
 * Notas de credito. No hay edit/update: se corrigen con sus lineas o se cancelan.
 */
Route::resource('notas-credito', NotaCreditoController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy'])
    ->parameters(['notas-credito' => 'nota'])
    ->middleware('permission:ventas.notas_credito.ver');

Route::post('notas-credito/{nota}/lineas', [NotaCreditoController::class, 'agregarLinea'])
    ->name('notas-credito.lineas.store')
    ->middleware('permission:ventas.notas_credito.editar');

Route::delete('notas-credito/{nota}/lineas/{linea}', [NotaCreditoController::class, 'eliminarLinea'])
    ->name('notas-credito.lineas.destroy')
    ->middleware('permission:ventas.notas_credito.editar');

Route::post('notas-credito/{nota}/emitir', [NotaCreditoController::class, 'emitir'])
    ->name('notas-credito.emitir')
    ->middleware('permission:ventas.notas_credito.emitir');

Route::post('notas-credito/{nota}/cancelar', [NotaCreditoController::class, 'cancelar'])
    ->name('notas-credito.cancelar')
    ->middleware('permission:ventas.notas_credito.emitir');

/*
 * Cobros.
 */
Route::resource('cobros', CobroController::class)
    ->middleware('permission:ventas.cobros.ver');

Route::post('cobros/{cobro}/aplicar', [CobroController::class, 'aplicar'])
    ->name('cobros.aplicar')
    ->middleware('permission:ventas.cobros.aplicar');

Route::patch('cobros/{cobro}/factura', [CobroController::class, 'asignarFactura'])
    ->name('cobros.asignar-factura')
    ->middleware('permission:ventas.cobros.editar');

Route::post('cobros/{cobro}/cancelar', [CobroController::class, 'cancelar'])
    ->name('cobros.cancelar')
    ->middleware('permission:ventas.cobros.aplicar');

/*
 * Reportes. Todos de solo lectura y todos con ?formato=csv.
 */
Route::middleware('permission:ventas.reportes.ver')->group(function (): void {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/por-periodo', [ReporteController::class, 'porPeriodo'])->name('reportes.por-periodo');
    Route::get('reportes/por-cliente', [ReporteController::class, 'porCliente'])->name('reportes.por-cliente');
    Route::get('reportes/por-producto', [ReporteController::class, 'porProducto'])->name('reportes.por-producto');
    Route::get('reportes/cuentas-por-cobrar', [ReporteController::class, 'cuentasPorCobrar'])->name('reportes.cuentas-por-cobrar');
    Route::get('reportes/devoluciones', [ReporteController::class, 'devoluciones'])->name('reportes.devoluciones');
});
