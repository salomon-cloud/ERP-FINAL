<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La nomina de v1 era una sola tabla: un recibo por empleado.
 *
 * El ERP la separa en los tres niveles que una nomina real necesita, sin perder
 * ni un dato de v1:
 *
 *   nomina_periodos  el calendario (la quincena)
 *   nomina_corridas  el procesamiento de un periodo
 *   nominas          el recibo por empleado -- la tabla v1, a la que solo se le
 *                    agrega corrida_id y unos campos de detalle
 *
 * Aplicar una corrida genera la poliza en Finanzas (sueldos, por pagar,
 * ISR e IMSS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_periodos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            // 2026-Q01
            $tabla->string('codigo_periodo', 30);
            $tabla->date('fecha_inicio');
            $tabla->date('fecha_fin');
            $tabla->date('fecha_pago');
            $tabla->string('frecuencia', 20)->default('quincenal');
            $tabla->string('estado', 20)->default('abierto');
            EsquemaErp::auditoria($tabla);

            $tabla->unique('codigo_periodo', 'uq_nomina_periodos_codigo');
            $tabla->index('estado', 'idx_nomina_periodos_estado');
        });

        EsquemaErp::check('nomina_periodos', 'chk_nomina_periodo_frecuencia', "frecuencia IN ('semanal','quincenal','mensual')");
        EsquemaErp::check('nomina_periodos', 'chk_nomina_periodo_estado', "estado IN ('abierto','procesado','cerrado')");
        EsquemaErp::check('nomina_periodos', 'chk_nomina_periodo_fechas', 'fecha_fin >= fecha_inicio');

        Schema::create('nomina_corridas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_corrida', 30);
            $tabla->foreignId('periodo_id')->constrained('nomina_periodos')->restrictOnDelete();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->integer('total_empleados')->default(0);
            EsquemaErp::dinero($tabla, 'total_percepciones')->default(0);
            EsquemaErp::dinero($tabla, 'total_deducciones')->default(0);
            EsquemaErp::dinero($tabla, 'total_neto')->default(0);
            $tabla->foreignId('poliza_id')->nullable()->constrained('polizas')->nullOnDelete();
            $tabla->timestamp('generada_en')->nullable();
            $tabla->foreignId('procesada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->foreignId('aprobada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('aplicada_en')->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_corrida', 'uq_nomina_corridas_numero');
            $tabla->index('estado', 'idx_nomina_corridas_estado');
        });

        EsquemaErp::check('nomina_corridas', 'chk_nomina_corrida_estado', "estado IN ('borrador','procesada','aplicada','cancelada')");
        EsquemaErp::check('nomina_corridas', 'chk_nomina_corrida_totales', 'total_empleados >= 0 AND total_percepciones >= 0 AND total_deducciones >= 0 AND total_neto >= 0');

        // La tabla `nominas` de v1 pasa a ser el detalle por empleado de una corrida.
        Schema::table('nominas', function (Blueprint $tabla) {
            $tabla->foreignId('corrida_id')->nullable()->after('empleado_id')
                ->constrained('nomina_corridas')->cascadeOnDelete();
            $tabla->decimal('horas_extra_cantidad', 8, 2)->default(0)->after('horas_extra');
            $tabla->decimal('dias_ausencia', 6, 2)->default(0)->after('deducciones');
            $tabla->timestamp('pagada_en')->nullable()->after('estado');
            $tabla->string('notas', 500)->nullable()->after('pagada_en');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            $tabla->index('estado', 'idx_nominas_estado');
        });

        EsquemaErp::check('nominas', 'chk_nominas_montos', 'sueldo_base >= 0 AND bonos >= 0 AND horas_extra >= 0 AND horas_extra_cantidad >= 0 AND deducciones >= 0 AND isr >= 0 AND imss >= 0 AND dias_ausencia >= 0');
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_nominas_estado');
            $tabla->dropConstrainedForeignId('corrida_id');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn(['horas_extra_cantidad', 'dias_ausencia', 'pagada_en', 'notas']);
        });

        Schema::dropIfExists('nomina_corridas');
        Schema::dropIfExists('nomina_periodos');
    }
};
