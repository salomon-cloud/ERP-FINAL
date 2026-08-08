<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * polizas y poliza_lineas: el corazon del sistema contable.
 *
 * Todo movimiento de dinero del ERP -- facturas, cobros, facturas de proveedor,
 * corridas de nomina, conciliaciones -- termina aqui a traves de
 * ServicioContabilizarPoliza, con su origen_tipo/origen_id lleno.
 *
 * Ciclo de vida: borrador -> contabilizada -> cancelada. Una poliza
 * contabilizada nunca se edita ni se borra: se cancela, y eso genera una poliza
 * de reverso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polizas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_poliza', 30);
            $tabla->foreignId('periodo_fiscal_id')->constrained('periodos_fiscales')->restrictOnDelete();
            $tabla->date('fecha');
            $tabla->string('concepto', 500);
            $tabla->string('referencia', 120)->nullable();
            $tabla->string('origen_tipo', 30);
            $tabla->unsignedBigInteger('origen_id')->nullable();
            $tabla->string('estado', 20)->default('borrador');
            EsquemaErp::dinero($tabla, 'total_debe')->default(0);
            EsquemaErp::dinero($tabla, 'total_haber')->default(0);
            $tabla->foreignId('contabilizada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('contabilizada_en')->nullable();
            $tabla->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->string('motivo_cancelacion', 500)->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_poliza', 'uq_polizas_numero');
            $tabla->index(['periodo_fiscal_id', 'fecha'], 'idx_polizas_periodo');
            $tabla->index('estado', 'idx_polizas_estado');
            $tabla->index(['origen_tipo', 'origen_id'], 'idx_polizas_origen');
        });

        EsquemaErp::check('polizas', 'chk_polizas_estado', "estado IN ('borrador','contabilizada','cancelada')");
        EsquemaErp::check('polizas', 'chk_polizas_origen', "origen_tipo IN ('manual','factura','nota_credito','cobro','factura_proveedor','devolucion_compra','nomina','cierre','conciliacion','ajuste')");
        EsquemaErp::check('polizas', 'chk_polizas_totales', 'total_debe >= 0 AND total_haber >= 0');

        Schema::create('poliza_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('poliza_id')->constrained('polizas')->cascadeOnDelete();
            $tabla->foreignId('cuenta_id')->constrained('catalogo_cuentas')->restrictOnDelete();
            // Cada linea lleva su propio periodo para poder reprocesar ajustes.
            $tabla->foreignId('periodo_fiscal_id')->constrained('periodos_fiscales')->restrictOnDelete();
            $tabla->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $tabla->string('concepto', 500)->nullable();
            EsquemaErp::dinero($tabla, 'debe')->default(0);
            EsquemaErp::dinero($tabla, 'haber')->default(0);
            $tabla->string('referencia', 120)->nullable();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index(['cuenta_id', 'periodo_fiscal_id'], 'idx_poliza_lineas_cuenta');
            $tabla->index('centro_costo_id', 'idx_poliza_lineas_centro');
        });

        // Una linea es cargo O abono: nunca las dos, nunca negativa.
        EsquemaErp::check('poliza_lineas', 'chk_poliza_lineas_un_lado', '(debe > 0 AND haber = 0) OR (debe = 0 AND haber > 0)');
        EsquemaErp::check('poliza_lineas', 'chk_poliza_lineas_sin_negativos', 'debe >= 0 AND haber >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('poliza_lineas');
        Schema::dropIfExists('polizas');
    }
};
