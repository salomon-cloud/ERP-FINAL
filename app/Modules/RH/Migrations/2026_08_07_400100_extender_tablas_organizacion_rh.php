<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * departamentos, puestos y empleados de SISEN v1 SON las tablas de RH del ERP.
 *
 * Esta migracion las eleva a nivel empresarial agregando columnas: jerarquia
 * (padre_id, jefe_id), codigo, datos fiscales y bancarios, auditoria y borrado
 * logico. Ninguna columna v1 se renombra ni se elimina, asi que las pantallas
 * actuales de empleados, departamentos y puestos siguen funcionando igual.
 *
 * departamentos.jefe_id -> empleados cierra un ciclo (empleados.departamento_id
 * apunta de vuelta), por eso se agrega al final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $tabla) {
            $tabla->foreignId('organizacion_id')->nullable()->after('id')
                ->constrained('organizaciones')->nullOnDelete();
            $tabla->foreignId('padre_id')->nullable()->after('organizacion_id')
                ->constrained('departamentos')->restrictOnDelete();
            $tabla->unsignedBigInteger('jefe_id')->nullable()->after('padre_id');
            $tabla->string('codigo', 30)->nullable()->after('jefe_id');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            $tabla->index('jefe_id', 'idx_departamentos_jefe');
        });

        EsquemaErp::unicoActivo('departamentos', 'codigo', 'uq_departamentos_codigo', 'VARCHAR(30)');

        Schema::table('puestos', function (Blueprint $tabla) {
            $tabla->string('codigo', 30)->nullable()->after('departamento_id');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);
        });

        EsquemaErp::unicoActivo('puestos', 'codigo', 'uq_puestos_codigo', 'VARCHAR(30)');
        EsquemaErp::check('puestos', 'chk_puestos_rango_sueldo', 'sueldo_maximo = 0 OR sueldo_maximo >= sueldo_minimo');

        Schema::table('empleados', function (Blueprint $tabla) {
            $tabla->foreignId('organizacion_id')->nullable()->after('id')
                ->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_empleado', 30)->nullable()->after('organizacion_id');
            $tabla->foreignId('jefe_id')->nullable()->after('puesto_id')
                ->constrained('empleados')->nullOnDelete();
            $tabla->string('genero', 20)->nullable()->after('apellidos');
            $tabla->date('fecha_baja')->nullable()->after('fecha_contratacion');
            $tabla->string('motivo_baja', 300)->nullable()->after('fecha_baja');
            $tabla->string('tipo_contrato', 30)->default('indefinido')->after('motivo_baja');
            $tabla->char('moneda', 3)->default('MXN')->after('sueldo_base');
            $tabla->string('frecuencia_pago', 20)->default('quincenal')->after('moneda');
            // NSS del IMSS
            $tabla->string('nss', 30)->nullable()->after('rfc');
            $tabla->string('banco', 150)->nullable()->after('nss');
            $tabla->string('cuenta_bancaria', 60)->nullable()->after('banco');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            $tabla->index('estado', 'idx_empleados_estado');
        });

        EsquemaErp::unicoActivo('empleados', 'numero_empleado', 'uq_empleados_numero', 'VARCHAR(30)');
        EsquemaErp::check('empleados', 'chk_empleados_genero', "genero IS NULL OR genero IN ('masculino','femenino','otro')");
        EsquemaErp::check('empleados', 'chk_empleados_tipo_contrato', "tipo_contrato IN ('indefinido','temporal','practicas','servicios','medio_tiempo')");
        EsquemaErp::check('empleados', 'chk_empleados_frecuencia_pago', "frecuencia_pago IN ('semanal','quincenal','mensual')");
        EsquemaErp::check('empleados', 'chk_empleados_fecha_baja', 'fecha_baja IS NULL OR fecha_baja >= fecha_contratacion');

        EsquemaErp::llaveForaneaDiferida('departamentos', 'jefe_id', 'empleados', 'fk_departamentos_jefe');
    }

    public function down(): void
    {
        EsquemaErp::eliminarLlaveForaneaDiferida('departamentos', 'fk_departamentos_jefe');

        EsquemaErp::eliminarCheckSiExiste('empleados', 'chk_empleados_genero');
        EsquemaErp::eliminarCheckSiExiste('empleados', 'chk_empleados_tipo_contrato');
        EsquemaErp::eliminarCheckSiExiste('empleados', 'chk_empleados_frecuencia_pago');
        EsquemaErp::eliminarCheckSiExiste('empleados', 'chk_empleados_fecha_baja');
        EsquemaErp::eliminarCheckSiExiste('puestos', 'chk_puestos_rango_sueldo');

        EsquemaErp::eliminarIndiceSiExiste('empleados', 'uq_empleados_numero');
        EsquemaErp::eliminarIndiceSiExiste('empleados', 'idx_empleados_estado');
        EsquemaErp::eliminarLlaveForaneaSiExiste('empleados', 'empleados_organizacion_id_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('empleados', 'empleados_jefe_id_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('empleados', 'empleados_creado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('empleados', 'empleados_actualizado_por_foreign');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'organizacion_id');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'jefe_id');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'creado_por');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'actualizado_por');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'numero_empleado_activo');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'deleted_at');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'numero_empleado');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'genero');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'fecha_baja');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'motivo_baja');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'tipo_contrato');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'moneda');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'frecuencia_pago');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'nss');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'banco');
        EsquemaErp::eliminarColumnaSiExiste('empleados', 'cuenta_bancaria');

        EsquemaErp::eliminarIndiceSiExiste('puestos', 'uq_puestos_codigo');
        EsquemaErp::eliminarLlaveForaneaSiExiste('puestos', 'puestos_creado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('puestos', 'puestos_actualizado_por_foreign');
        EsquemaErp::eliminarColumnaSiExiste('puestos', 'creado_por');
        EsquemaErp::eliminarColumnaSiExiste('puestos', 'actualizado_por');
        EsquemaErp::eliminarColumnaSiExiste('puestos', 'codigo_activo');
        EsquemaErp::eliminarColumnaSiExiste('puestos', 'deleted_at');
        EsquemaErp::eliminarColumnaSiExiste('puestos', 'codigo');

        EsquemaErp::eliminarIndiceSiExiste('departamentos', 'uq_departamentos_codigo');
        EsquemaErp::eliminarIndiceSiExiste('departamentos', 'idx_departamentos_jefe');
        EsquemaErp::eliminarLlaveForaneaSiExiste('departamentos', 'departamentos_organizacion_id_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('departamentos', 'departamentos_padre_id_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('departamentos', 'departamentos_creado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('departamentos', 'departamentos_actualizado_por_foreign');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'organizacion_id');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'padre_id');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'creado_por');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'actualizado_por');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'codigo_activo');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'deleted_at');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'jefe_id');
        EsquemaErp::eliminarColumnaSiExiste('departamentos', 'codigo');
    }
};
