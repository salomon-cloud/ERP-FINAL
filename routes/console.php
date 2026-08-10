<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Necesitan un cron en el servidor que llame a `php artisan schedule:run`
| cada minuto; Laravel decide desde aqui que corre y cuando.
|
| El reorden se revisa una vez al dia, temprano, para que el aviso de stock
| bajo este listo antes de que abra el area de compras. `withoutOverlapping`
| evita que dos ejecuciones se pisen si el catalogo crece mucho.
|
*/
Schedule::command('inventario:reorden')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->description('Revisa los minimos de inventario y avisa de lo que hay que comprar');
