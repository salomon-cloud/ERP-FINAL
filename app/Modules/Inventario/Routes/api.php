<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Inventario - rutas del API JSON
|--------------------------------------------------------------------------
|
| Las registra App\Providers\ModuleServiceProvider bajo el prefijo de URL
| "/api/inventario" y el prefijo de nombre "api.inventario.".
|
| Es un API del MISMO ORIGEN: se autentica con la cookie de sesion, no con un
| token, y por eso corre en el grupo 'web'. El dia que haya un consumidor
| externo se cambia a auth:sanctum en el provider.
|
| Hoy publica una sola cosa, y es la que hace usable el ERP entero: el
| autocompletado de producto que alimenta los carritos de Ventas y Compras.
|
*/

use App\Modules\Inventario\Controllers\Api\ProductoController;
use Illuminate\Support\Facades\Route;

Route::get('productos/buscar', ProductoController::class)
    ->name('productos.buscar')
    ->middleware('permission:inventario.productos.ver,ventas.pedidos.crear,compras.ordenes.crear');
