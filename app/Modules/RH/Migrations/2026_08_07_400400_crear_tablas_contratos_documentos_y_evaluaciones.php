<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * contratos, documentos_empleado y evaluaciones_desempeno.
 *
 * El archivo binario de un documento vive en la tabla compartida `adjuntos`;
 * aqui se guarda el metadato de RH (tipo, vigencia, estado de revision).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $tabla->string('numero_contrato', 30)->nullable();
            $tabla->string('tipo_contrato', 30)->default('indefinido');
            $tabla->date('fecha_inicio');
            $tabla->date('fecha_fin')->nullable();
            EsquemaErp::dinero($tabla, 'sueldo')->default(0);
            $tabla->decimal('jornada_horas', 6, 2)->default(48);
            $tabla->text('resumen_clausulas')->nullable();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->timestamp('firmado_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index(['empleado_id', 'fecha_inicio'], 'idx_contratos_empleado');
            $tabla->index('estado', 'idx_contratos_estado');
        });

        EsquemaErp::check('contratos', 'chk_contratos_tipo', "tipo_contrato IN ('indefinido','temporal','practicas','servicios','medio_tiempo')");
        EsquemaErp::check('contratos', 'chk_contratos_estado', "estado IN ('borrador','vigente','vencido','terminado')");
        EsquemaErp::check('contratos', 'chk_contratos_fechas', 'fecha_fin IS NULL OR fecha_fin >= fecha_inicio');
        EsquemaErp::check('contratos', 'chk_contratos_valores', 'sueldo >= 0 AND jornada_horas > 0');

        Schema::create('documentos_empleado', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $tabla->foreignId('adjunto_id')->nullable()->constrained('adjuntos')->nullOnDelete();
            $tabla->string('tipo_documento', 30);
            $tabla->string('titulo', 200);
            $tabla->date('vigencia')->nullable();
            $tabla->string('estado', 20)->default('vigente');
            EsquemaErp::auditoria($tabla);

            $tabla->index('empleado_id', 'idx_documentos_empleado');
            $tabla->index('vigencia', 'idx_documentos_vigencia');
        });

        EsquemaErp::check('documentos_empleado', 'chk_documentos_tipo', "tipo_documento IN ('contrato','identificacion','fiscal','salud','academico','otro')");
        EsquemaErp::check('documentos_empleado', 'chk_documentos_estado', "estado IN ('vigente','vencido','pendiente')");

        Schema::create('evaluaciones_desempeno', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $tabla->foreignId('evaluador_id')->nullable()->constrained('empleados')->nullOnDelete();
            // 2026-S1
            $tabla->string('periodo_evaluado', 50);
            $tabla->decimal('calificacion', 5, 2)->nullable();
            $tabla->text('fortalezas')->nullable();
            $tabla->text('areas_mejora')->nullable();
            // Lista de objetos { objetivo, metrica, meta, logrado }
            $tabla->json('objetivos')->nullable();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->timestamp('evaluado_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['empleado_id', 'periodo_evaluado'], 'uq_evaluacion_periodo');
            $tabla->index('estado', 'idx_evaluaciones_estado');
        });

        EsquemaErp::check('evaluaciones_desempeno', 'chk_evaluaciones_estado', "estado IN ('borrador','enviada','reconocida')");
        EsquemaErp::check('evaluaciones_desempeno', 'chk_evaluaciones_calificacion', 'calificacion IS NULL OR (calificacion >= 0 AND calificacion <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_desempeno');
        Schema::dropIfExists('documentos_empleado');
        Schema::dropIfExists('contratos');
    }
};
