<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * asistencias y permisos de v1, elevadas a nivel empresarial.
 *
 * Las columnas y los valores de estado de v1 (presente|falta|retardo|permiso,
 * pendiente|aprobado|rechazado) se respetan tal cual, porque las vistas y los
 * badges de v1 dependen de ellos. Lo que se agrega son las horas trabajadas, el
 * rastro de quien reviso, y la auditoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $tabla) {
            $tabla->decimal('horas_trabajadas', 8, 2)->default(0)->after('hora_salida');
            $tabla->string('notas', 500)->nullable()->after('estado');
            $tabla->foreignId('verificado_por')->nullable()->after('notas')
                ->constrained('users')->nullOnDelete();
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            // Un empleado tiene una sola asistencia por dia.
            $tabla->unique(['empleado_id', 'fecha'], 'uq_asistencias_empleado_fecha');
            $tabla->index('fecha', 'idx_asistencias_fecha');
            $tabla->index('estado', 'idx_asistencias_estado');
        });

        EsquemaErp::check('asistencias', 'chk_asistencias_horas', 'horas_trabajadas >= 0');
        EsquemaErp::check('asistencias', 'chk_asistencias_horario', 'hora_salida IS NULL OR hora_entrada IS NULL OR hora_salida >= hora_entrada');

        Schema::table('permisos', function (Blueprint $tabla) {
            $tabla->decimal('dias', 6, 2)->default(0)->after('fecha_fin');
            // Un permiso sin goce descuenta en la nomina.
            $tabla->boolean('con_goce')->default(true)->after('dias');
            $tabla->foreignId('revisado_por')->nullable()->after('estado')
                ->constrained('users')->nullOnDelete();
            $tabla->timestamp('revisado_en')->nullable()->after('revisado_por');
            $tabla->string('comentario_revision', 500)->nullable()->after('revisado_en');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            $tabla->index(['empleado_id', 'fecha_inicio'], 'idx_permisos_empleado');
            $tabla->index('estado', 'idx_permisos_estado');
        });

        EsquemaErp::check('permisos', 'chk_permisos_dias', 'dias >= 0');
        EsquemaErp::check('permisos', 'chk_permisos_fechas', 'fecha_fin >= fecha_inicio');
    }

    public function down(): void
    {
        EsquemaErp::eliminarIndiceSiExiste('permisos', 'idx_permisos_empleado');
        EsquemaErp::eliminarIndiceSiExiste('permisos', 'idx_permisos_estado');
        EsquemaErp::eliminarLlaveForaneaSiExiste('permisos', 'permisos_revisado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('permisos', 'permisos_creado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('permisos', 'permisos_actualizado_por_foreign');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'revisado_por');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'creado_por');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'actualizado_por');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'deleted_at');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'dias');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'con_goce');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'revisado_en');
        EsquemaErp::eliminarColumnaSiExiste('permisos', 'comentario_revision');

        EsquemaErp::eliminarIndiceSiExiste('asistencias', 'uq_asistencias_empleado_fecha');
        EsquemaErp::eliminarIndiceSiExiste('asistencias', 'idx_asistencias_fecha');
        EsquemaErp::eliminarIndiceSiExiste('asistencias', 'idx_asistencias_estado');
        EsquemaErp::eliminarLlaveForaneaSiExiste('asistencias', 'asistencias_verificado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('asistencias', 'asistencias_creado_por_foreign');
        EsquemaErp::eliminarLlaveForaneaSiExiste('asistencias', 'asistencias_actualizado_por_foreign');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'verificado_por');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'creado_por');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'actualizado_por');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'deleted_at');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'horas_trabajadas');
        EsquemaErp::eliminarColumnaSiExiste('asistencias', 'notas');
    }
};
