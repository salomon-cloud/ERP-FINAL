<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * presupuestos y presupuesto_lineas.
 *
 * monto_real es una foto de conveniencia; la comparacion que manda es
 * v_presupuesto_vs_real, que lee directamente las lineas contabilizadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->foreignId('periodo_fiscal_id')->constrained('periodos_fiscales')->restrictOnDelete();
            $tabla->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $tabla->string('nombre', 150);
            $tabla->string('estado', 20)->default('borrador');
            EsquemaErp::dinero($tabla, 'monto_total')->default(0);
            $tabla->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('aprobado_en')->nullable();
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::check('presupuestos', 'chk_presupuestos_estado', "estado IN ('borrador','aprobado','cerrado')");
        EsquemaErp::check('presupuestos', 'chk_presupuestos_total', 'monto_total >= 0');

        Schema::create('presupuesto_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $tabla->foreignId('cuenta_id')->constrained('catalogo_cuentas')->restrictOnDelete();
            $tabla->smallInteger('mes');
            EsquemaErp::dinero($tabla, 'monto_proyectado')->default(0);
            EsquemaErp::dinero($tabla, 'monto_real')->default(0);
            $tabla->timestamps();

            $tabla->unique(['presupuesto_id', 'cuenta_id', 'mes'], 'uq_presupuesto_linea_cuenta');
            $tabla->index('cuenta_id', 'idx_presupuesto_lineas_cuenta');
        });

        EsquemaErp::check('presupuesto_lineas', 'chk_presupuesto_linea_mes', 'mes BETWEEN 1 AND 12');
        EsquemaErp::check('presupuesto_lineas', 'chk_presupuesto_linea_montos', 'monto_proyectado >= 0 AND monto_real >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_lineas');
        Schema::dropIfExists('presupuestos');
    }
};
