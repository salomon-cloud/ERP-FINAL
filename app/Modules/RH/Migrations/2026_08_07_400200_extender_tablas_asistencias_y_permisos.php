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
        Schema::table('permisos', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_permisos_empleado');
            $tabla->dropIndex('idx_permisos_estado');
            $tabla->dropConstrainedForeignId('revisado_por');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn(['dias', 'con_goce', 'revisado_en', 'comentario_revision']);
        });

        Schema::table('asistencias', function (Blueprint $tabla) {
            $tabla->dropUnique('uq_asistencias_empleado_fecha');
            $tabla->dropIndex('idx_asistencias_fecha');
            $tabla->dropIndex('idx_asistencias_estado');
            $tabla->dropConstrainedForeignId('verificado_por');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn(['horas_trabajadas', 'notas']);
        });
    }
};
