<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Inventario - rutas del API JSON
|--------------------------------------------------------------------------
|
| Las registra automaticamente App\Providers\ModuleServiceProvider bajo el
| prefijo de URL "/api/inventario" y el prefijo de nombre "api.inventario.".
| La autenticacion por sesion ya esta aplicada a este grupo.
|
| Los controladores viven en Controllers/Api y reutilizan LOS MISMOS
| FormRequest y servicios que la capa web: nunca dupliques logica de negocio.
|
|     Route::apiResource('pedidos', Api\PedidoApiController::class)
|         ->middleware('permission:inventario.pedidos.ver');
|
| Ante un error, todo endpoint responde el sobre JSON
| { "message": ..., "errors": ... }. Ver docs/PLANNING.md - "API Structure".
|
| Vacio hasta que el modulo exponga su primer endpoint. Nunca uses un closure:
| las rutas deben sobrevivir a `php artisan route:cache`.
|
*/

use Illuminate\Support\Facades\Route;
