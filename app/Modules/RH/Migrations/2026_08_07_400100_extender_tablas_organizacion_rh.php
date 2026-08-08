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

        Schema::table('empleados', function (Blueprint $tabla) {
            $tabla->dropIndex('uq_empleados_numero');
            $tabla->dropColumn('numero_empleado_activo');
            $tabla->dropIndex('idx_empleados_estado');
            $tabla->dropConstrainedForeignId('organizacion_id');
            $tabla->dropConstrainedForeignId('jefe_id');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn([
                'numero_empleado', 'genero', 'fecha_baja', 'motivo_baja', 'tipo_contrato',
                'moneda', 'frecuencia_pago', 'nss', 'banco', 'cuenta_bancaria',
            ]);
        });

        Schema::table('puestos', function (Blueprint $tabla) {
            $tabla->dropIndex('uq_puestos_codigo');
            $tabla->dropColumn('codigo_activo');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn('codigo');
        });

        Schema::table('departamentos', function (Blueprint $tabla) {
            $tabla->dropIndex('uq_departamentos_codigo');
            $tabla->dropColumn('codigo_activo');
            $tabla->dropIndex('idx_departamentos_jefe');
            $tabla->dropConstrainedForeignId('organizacion_id');
            $tabla->dropConstrainedForeignId('padre_id');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn(['jefe_id', 'codigo']);
        });
    }
};
