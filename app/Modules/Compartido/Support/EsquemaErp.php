<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

/**
 * Utilidades compartidas por todas las migraciones de los modulos.
 *
 * SISEN corre sobre MariaDB y nada mas. Estas funciones garantizan que el
 * conjunto de columnas universal, las precisiones de dinero/cantidad y la
 * estrategia de restricciones sean identicas en los siete modulos.
 *
 * Ver docs/PLANNING.md - "Database Standards".
 */
final class EsquemaErp
{
    /** Dinero: DECIMAL(18,2). Nunca un flotante. */
    public static function dinero(Blueprint $tabla, string $columna): ColumnDefinition
    {
        return $tabla->decimal($columna, 18, 2);
    }

    /** Tasas y cantidades de inventario: DECIMAL(18,6). */
    public static function cantidad(Blueprint $tabla, string $columna): ColumnDefinition
    {
        return $tabla->decimal($columna, 18, 6);
    }

    /**
     * Conjunto de columnas universal de una tabla de negocio:
     * created_at, updated_at, deleted_at, creado_por, actualizado_por.
     *
     * Las tres primeras conservan el nombre que Laravel espera (y que ya usa
     * SISEN v1); las de autoria van en espanol porque son nuestras.
     */
    public static function auditoria(Blueprint $tabla): void
    {
        $tabla->timestamps();
        $tabla->softDeletes();
        self::autores($tabla);
    }

    /** Solo creado_por / actualizado_por (tablas sin borrado logico). */
    public static function autores(Blueprint $tabla): void
    {
        $tabla->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        $tabla->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
    }

    /** Bloqueo optimista de los documentos principales. */
    public static function versionFila(Blueprint $tabla): void
    {
        $tabla->integer('version_fila')->default(1);
    }

    /**
     * Restriccion CHECK. MariaDB las valida desde 10.2, asi que son una
     * garantia real de integridad y no solo documentacion.
     *
     * La misma regla vive tambien en el FormRequest y en el Service: la base de
     * datos es la ultima linea de defensa, no la unica.
     */
    public static function check(string $tabla, string $nombre, string $expresion): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s)',
            self::envolverTabla($tabla),
            self::envolver($nombre),
            $expresion
        ));
    }

    /**
     * Indice unico que ignora los registros con borrado logico, para que un
     * codigo o un correo puedan reutilizarse despues de eliminar a su dueno
     * (PLANNING - Database Standards 3).
     *
     * MariaDB no tiene indices parciales, asi que el equivalente es una columna
     * generada virtual que vale NULL en las filas borradas -- y dos NULL nunca
     * chocan en un indice unico -- con el indice unico encima.
     */
    public static function unicoActivo(string $tabla, string $columna, string $nombre, string $tipo = 'VARCHAR(191)'): void
    {
        $generada = $columna.'_activo';

        DB::statement(sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s AS (CASE WHEN deleted_at IS NULL THEN %s END) VIRTUAL',
            self::envolverTabla($tabla),
            self::envolver($generada),
            $tipo,
            self::envolver($columna)
        ));

        DB::statement(sprintf(
            'ALTER TABLE %s ADD UNIQUE INDEX %s (%s)',
            self::envolverTabla($tabla),
            self::envolver($nombre),
            self::envolver($generada)
        ));
    }

    /**
     * Llave foranea que cierra una referencia circular y por eso se agrega
     * cuando ya existen las dos tablas.
     */
    public static function llaveForaneaDiferida(
        string $tabla,
        string $columna,
        string $tablaReferida,
        string $nombre,
        string $alBorrar = 'SET NULL'
    ): void {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE %s',
            self::envolverTabla($tabla),
            self::envolver($nombre),
            self::envolver($columna),
            self::envolverTabla($tablaReferida),
            $alBorrar
        ));
    }

    public static function eliminarLlaveForaneaDiferida(string $tabla, string $nombre): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            self::envolverTabla($tabla),
            self::envolver($nombre)
        ));
    }

    /**
     * Crea una vista de reporte. Siempre se elimina primero para que la
     * migracion pueda volver a ejecutarse sin sorpresas.
     */
    public static function crearVista(string $nombre, string $consulta): void
    {
        self::eliminarVista($nombre);

        DB::statement('CREATE VIEW '.self::envolverTabla($nombre).' AS '.$consulta);
    }

    public static function eliminarVista(string $nombre): void
    {
        DB::statement('DROP VIEW IF EXISTS '.self::envolverTabla($nombre));
    }

    private static function envolverTabla(string $tabla): string
    {
        return DB::getQueryGrammar()->wrapTable($tabla);
    }

    private static function envolver(string $valor): string
    {
        return DB::getQueryGrammar()->wrap($valor);
    }
}
