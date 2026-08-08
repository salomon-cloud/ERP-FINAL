<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Compartido (Cimientos) - rutas de navegador
|--------------------------------------------------------------------------
|
| Las registra automaticamente App\Providers\ModuleServiceProvider en la RAIZ
| de la aplicacion (sin prefijo de URL) con el prefijo de nombre "compartido.".
| Los middleware 'web' y 'auth' YA estan aplicados a este grupo.
|
| Compartido es infraestructura, asi que es dueno de paginas transversales en
| vez de un area de negocio. Reservado para:
|
|     Route::get('notificaciones', ...)->name('notificaciones.index');
|     Route::patch('notificaciones/{notificacion}/leer', ...)->name('notificaciones.leer');
|     Route::get('configuraciones', ...)->name('configuraciones.index')
|         ->middleware('permission:compartido.configuraciones.administrar');
|
| Vacio a proposito: el equipo de cimientos lo llena en el hito M1. Nunca uses
| un closure: las rutas deben sobrevivir a `php artisan route:cache`.
|
*/

use Illuminate\Support\Facades\Route;
