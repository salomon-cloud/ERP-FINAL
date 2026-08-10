<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Compartido\Support\RegistroMenu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Descubre cada carpeta bajo app/Modules y la conecta a la aplicacion: rutas de
 * navegador, rutas del API JSON, namespace de vistas Blade y migraciones.
 *
 * Agregar un modulo = agregar una carpeta. Ni routes/web.php ni este provider
 * se tocan. Ver docs/PLANNING.md - "Global Architecture / Runtime model".
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Modulos cuyas rutas de navegador viven en la raiz de la aplicacion en vez
     * de bajo su propio prefijo. Compartido es dueno de paginas transversales
     * (/notificaciones, /configuraciones) que leerian mal como
     * /compartido/notificaciones.
     */
    private const MODULOS_SIN_PREFIJO = ['Compartido'];

    public function boot(): void
    {
        foreach (static::modulos() as $modulo => $ruta) {
            $this->registrarVistas($modulo, $ruta);
            $this->registrarMigraciones($ruta);
            $this->registrarRutas($modulo, $ruta);
            $this->registrarComandos($modulo, $ruta);
            $this->registrarMenu($modulo);
        }
    }

    /**
     * Los modulos descubiertos como [Nombre => ruta absoluta], alfabeticamente.
     *
     * @return array<string, string>
     */
    public static function modulos(): array
    {
        $base = app_path('Modules');

        if (! is_dir($base)) {
            return [];
        }

        $modulos = [];

        foreach (scandir($base) ?: [] as $entrada) {
            if ($entrada === '.' || $entrada === '..') {
                continue;
            }

            $ruta = $base.DIRECTORY_SEPARATOR.$entrada;

            if (is_dir($ruta)) {
                $modulos[$entrada] = $ruta;
            }
        }

        ksort($modulos);

        return $modulos;
    }

    /**
     * Prefijo de URL y de nombre de ruta de un modulo: Finanzas => finanzas,
     * RH => rh, CRM => crm. Por convencion los nombres de modulo son de una
     * sola palabra, asi que basta con pasarlos a minusculas (Str::kebab
     * produciria "c-r-m").
     */
    public static function slug(string $modulo): string
    {
        return strtolower($modulo);
    }

    private function registrarVistas(string $modulo, string $ruta): void
    {
        $vistas = $ruta.'/Views';

        if (is_dir($vistas)) {
            $this->loadViewsFrom($vistas, static::slug($modulo));
        }
    }

    private function registrarMigraciones(string $ruta): void
    {
        $migraciones = $ruta.'/Migrations';

        if (is_dir($migraciones)) {
            $this->loadMigrationsFrom($migraciones);
        }
    }

    /**
     * Los comandos de consola del modulo (Console/*.php).
     *
     * Laravel solo descubre solo los de app/Console/Commands, que esta fuera de
     * los modulos. Sin esto, `inventario:reorden` no existiria para artisan y
     * no se podria programar en routes/console.php.
     */
    private function registrarComandos(string $modulo, string $ruta): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $carpeta = $ruta.'/Console';

        if (! is_dir($carpeta)) {
            return;
        }

        $comandos = [];

        foreach (glob($carpeta.'/*.php') ?: [] as $archivo) {
            $clase = "App\\Modules\\{$modulo}\\Console\\".basename($archivo, '.php');

            if (class_exists($clase)) {
                $comandos[] = $clase;
            }
        }

        if ($comandos !== []) {
            $this->commands($comandos);
        }
    }

    private function registrarRutas(string $modulo, string $ruta): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $slug = static::slug($modulo);
        $web = $ruta.'/Routes/web.php';
        $api = $ruta.'/Routes/api.php';

        // Los dos middleware van en UNA sola llamada: encadenar ->middleware()
        // dos veces sobrescribe el valor anterior en vez de sumarlo, y el grupo
        // se quedaria sin sesion, sin CSRF y sin la variable $errors de las vistas.
        if (is_file($web)) {
            Route::middleware(['web', 'auth'])
                ->prefix(in_array($modulo, self::MODULOS_SIN_PREFIJO, true) ? '' : $slug)
                ->name($slug.'.')
                ->group($web);
        }

        // El API JSON es del mismo origen, asi que se autentica con la cookie de
        // sesion y por eso necesita el grupo 'web' (StartSession + CSRF) y no el
        // grupo 'api' sin estado. Cuando aparezca el primer consumidor externo se
        // cambia a ['api','auth:sanctum'] -- ver PLANNING "API Structure".
        if (is_file($api)) {
            Route::middleware(['web', 'auth'])
                ->prefix('api/'.$slug)
                ->name('api.'.$slug.'.')
                ->group($api);
        }
    }

    /**
     * Cada modulo obtiene gratis su entrada de tablero en la barra lateral. Un
     * modulo agrega las suyas con RegistroMenu::registrar(); el layout no cambia.
     */
    private function registrarMenu(string $modulo): void
    {
        if (in_array($modulo, self::MODULOS_SIN_PREFIJO, true)) {
            return;
        }

        $metadatos = RegistroMenu::metadatos($modulo);

        RegistroMenu::registrar($modulo, [[
            'etiqueta' => $metadatos['etiqueta'],
            'icono' => $metadatos['icono'],
            'ruta' => static::slug($modulo).'.dashboard',
        ]]);
    }
}
