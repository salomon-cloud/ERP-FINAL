<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuentas bancarias, movimientos del estado de cuenta y conciliacion.
 *
 * Una linea de conciliacion empareja un movimiento bancario con la poliza que
 * deberia corresponderle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('nombre', 150);
            $tabla->string('banco', 150);
            $tabla->string('numero_cuenta', 60);
            $tabla->string('tipo_cuenta', 20)->default('cheques');
            $tabla->char('moneda', 3)->default('MXN');
            EsquemaErp::dinero($tabla, 'saldo_inicial')->default(0);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::check('cuentas_bancarias', 'chk_cuentas_bancarias_tipo', "tipo_cuenta IN ('cheques','ahorro','tarjeta_credito')");

        Schema::create('movimientos_bancarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->cascadeOnDelete();
            $tabla->date('fecha');
            $tabla->string('concepto', 300);
            EsquemaErp::dinero($tabla, 'monto');
            $tabla->string('estado', 20)->default('sin_conciliar');
            $tabla->string('referencia', 120)->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index(['cuenta_bancaria_id', 'fecha'], 'idx_mov_bancarios_cuenta_fecha');
            $tabla->index('estado', 'idx_mov_bancarios_estado');
        });

        EsquemaErp::check('movimientos_bancarios', 'chk_mov_bancarios_monto', 'monto <> 0');
        EsquemaErp::check('movimientos_bancarios', 'chk_mov_bancarios_estado', "estado IN ('sin_conciliar','conciliado','pendiente','cancelado')");

        Schema::create('conciliaciones_bancarias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->cascadeOnDelete();
            $tabla->date('fecha_inicio');
            $tabla->date('fecha_fin');
            $tabla->string('estado', 20)->default('abierta');
            EsquemaErp::dinero($tabla, 'saldo_inicial')->default(0);
            EsquemaErp::dinero($tabla, 'saldo_final')->default(0);
            $tabla->foreignId('conciliada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('conciliada_en')->nullable();
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::check('conciliaciones_bancarias', 'chk_conciliaciones_estado', "estado IN ('abierta','conciliada','cerrada')");
        EsquemaErp::check('conciliaciones_bancarias', 'chk_conciliaciones_fechas', 'fecha_fin >= fecha_inicio');

        Schema::create('conciliacion_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('conciliacion_id')->constrained('conciliaciones_bancarias')->cascadeOnDelete();
            $tabla->foreignId('movimiento_bancario_id')->constrained('movimientos_bancarios')->restrictOnDelete();
            $tabla->foreignId('poliza_id')->nullable()->constrained('polizas')->restrictOnDelete();
            $tabla->foreignId('conciliada_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('conciliada_en')->useCurrent();

            $tabla->unique(['conciliacion_id', 'movimiento_bancario_id'], 'uq_conciliacion_linea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliacion_lineas');
        Schema::dropIfExists('conciliaciones_bancarias');
        Schema::dropIfExists('movimientos_bancarios');
        Schema::dropIfExists('cuentas_bancarias');
    }
};
