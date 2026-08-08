<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Regenera ERP.sql a partir de las migraciones.
 *
 *     php artisan sisen:esquema
 *
 * ERP.sql NO se importa nunca: el esquema se crea y evoluciona solo con
 * `php artisan migrate`. El archivo existe como documento de referencia -- el
 * contrato de base de datos que los seis equipos leen -- y por eso se GENERA en
 * vez de escribirse a mano: asi no puede desviarse de lo que realmente crean
 * las migraciones.
 *
 * El volcado se toma de una base temporal recien migrada, no de la base de
 * desarrollo, para que ninguna prueba manual se cuele en el contrato.
 */
class GenerarEsquemaSql extends Command
{
    protected $signature = 'sisen:esquema {--archivo=ERP.sql : Ruta de salida, relativa a la raiz del proyecto}';

    protected $description = 'Regenera ERP.sql (esquema de referencia MariaDB) desde las migraciones';

    public function handle(): int
    {
        $conexion = config('database.default');
        $configuracion = config("database.connections.{$conexion}");
        $temporal = $configuracion['database'].'_esquema_tmp';

        $this->components->info("Generando el esquema en la base temporal [{$temporal}]...");

        DB::statement("DROP DATABASE IF EXISTS `{$temporal}`");
        DB::statement("CREATE DATABASE `{$temporal}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Apunta la conexion a la base temporal mientras dura el volcado, para
        // que las migraciones no toquen la base de desarrollo.
        config(["database.connections.{$conexion}.database" => $temporal]);
        DB::purge($conexion);

        try {
            $this->call('migrate', ['--force' => true, '--no-interaction' => true]);

            $volcado = $this->volcar($configuracion, $temporal);
        } finally {
            config(["database.connections.{$conexion}.database" => $configuracion['database']]);
            DB::purge($conexion);
            DB::statement("DROP DATABASE IF EXISTS `{$temporal}`");
        }

        $destino = base_path($this->option('archivo'));
        file_put_contents($destino, $this->encabezado().$volcado);

        $this->components->info('Escrito '.$this->option('archivo').' ('.number_format(strlen($volcado) / 1024, 1).' KB)');

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $configuracion */
    private function volcar(array $configuracion, string $base): string
    {
        $argumentos = [
            'mysqldump',
            '--no-data',
            '--skip-comments',
            '--skip-set-charset',
            '--single-transaction',
            '--routines=FALSE',
            '--host='.$configuracion['host'],
            '--port='.$configuracion['port'],
            '--user='.$configuracion['username'],
        ];

        if (! empty($configuracion['password'])) {
            $argumentos[] = '--password='.$configuracion['password'];
        }

        $argumentos[] = $base;

        $proceso = new Process($argumentos, base_path());
        $proceso->setTimeout(120);
        $proceso->mustRun();

        $volcado = $proceso->getOutput();

        // Dos cosas del volcado son estado del servidor, no parte del contrato, y
        // harian que el archivo cambiara en cada regeneracion: el contador de
        // AUTO_INCREMENT y la posicion de replicacion (GTID / SQL_LOG_BIN).
        $volcado = preg_replace('/ AUTO_INCREMENT=\d+/', '', $volcado) ?? '';
        $volcado = preg_replace('/^SET @@(GLOBAL\.GTID_PURGED|SESSION\.SQL_LOG_BIN).*\n/m', '', $volcado) ?? '';

        return $volcado;
    }

    private function encabezado(): string
    {
        $fecha = now()->toDateString();

        return <<<SQL
            -- ============================================================================
            -- SISEN ERP - Esquema de referencia (MariaDB)
            -- ============================================================================
            -- GENERADO AUTOMATICAMENTE. No lo edites a mano: se sobrescribe.
            --
            --     php artisan sisen:esquema
            --
            -- Este archivo NUNCA se importa. El esquema de la aplicacion se crea y
            -- evoluciona exclusivamente con migraciones:
            --
            --     php artisan migrate
            --
            -- Existe como CONTRATO DE BASE DE DATOS entre los seis equipos de modulo: es
            -- el reflejo exacto de lo que producen las migraciones de app/Modules/*/Migrations,
            -- volcado desde una base recien migrada, sin datos.
            --
            -- Motor    : MariaDB / MySQL 8+ (InnoDB, utf8mb4)
            -- Generado : {$fecha}
            -- Fuente   : app/Modules/{Compartido,Finanzas,Inventario,RH,Ventas,Compras,CRM}/Migrations
            --            database/migrations (tablas de SISEN v1 y del framework)
            --
            -- Convenciones aplicadas en todo el esquema:
            --   * id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            --   * created_at / updated_at / deleted_at  (nombres de Laravel, como en v1)
            --   * creado_por / actualizado_por          BIGINT NULL -> users(id)
            --   * version_fila  INT NOT NULL DEFAULT 1  (bloqueo optimista, solo documentos)
            --   * dinero        DECIMAL(18,2)           nunca punto flotante
            --   * tasas y cantidades  DECIMAL(18,6)
            --   * enumeraciones mediante restricciones CHECK
            --   * llaves naturales unicas mediante una columna generada <col>_activo,
            --     que vale NULL en las filas con borrado logico: asi un codigo puede
            --     reutilizarse despues de eliminar a su dueno
            --   * toda llave foranea indexada y con ON DELETE explicito
            -- ============================================================================


            SQL;
    }
}
