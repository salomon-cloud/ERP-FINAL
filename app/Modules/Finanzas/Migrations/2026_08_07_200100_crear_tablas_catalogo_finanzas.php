<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos maestros de Finanzas: catalogo de cuentas, periodos fiscales, centros
 * de costo, impuestos y tipos de cambio.
 *
 * Corre antes que Inventario porque los productos apuntan a impuestos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_cuentas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->foreignId('padre_id')->nullable()->constrained('catalogo_cuentas')->restrictOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 200);
            $tabla->string('tipo_cuenta', 30);
            $tabla->string('naturaleza', 10);
            $tabla->boolean('es_encabezado')->default(false);
            $tabla->boolean('permite_movimientos')->default(true);
            // Marca las cuentas de caja y bancos; alimenta v_flujo_efectivo.
            $tabla->boolean('es_efectivo')->default(false);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);

            $tabla->index('tipo_cuenta', 'idx_cuentas_tipo');
        });

        EsquemaErp::unicoActivo('catalogo_cuentas', 'codigo', 'uq_cuentas_codigo', 'VARCHAR(30)');
        EsquemaErp::check('catalogo_cuentas', 'chk_cuentas_tipo', "tipo_cuenta IN ('activo','pasivo','capital','ingreso','egreso','activo_contra','pasivo_contra','capital_contra','ingreso_contra','egreso_contra')");
        EsquemaErp::check('catalogo_cuentas', 'chk_cuentas_naturaleza', "naturaleza IN ('deudora','acreedora')");
        // Una cuenta de encabezado organiza el arbol; solo las hojas reciben movimientos.
        EsquemaErp::check('catalogo_cuentas', 'chk_cuentas_encabezado', 'es_encabezado = FALSE OR permite_movimientos = FALSE');

        Schema::create('periodos_fiscales', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('nombre', 80);
            $tabla->smallInteger('ejercicio');
            $tabla->date('fecha_inicio');
            $tabla->date('fecha_fin');
            $tabla->string('estado', 20)->default('abierto');
            $tabla->boolean('es_periodo_cierre')->default(false);
            $tabla->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('cerrado_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['ejercicio', 'nombre'], 'uq_periodos_ejercicio_nombre');
            $tabla->index('estado', 'idx_periodos_estado');
        });

        EsquemaErp::check('periodos_fiscales', 'chk_periodos_estado', "estado IN ('abierto','cerrado','bloqueado')");
        EsquemaErp::check('periodos_fiscales', 'chk_periodos_fechas', 'fecha_fin >= fecha_inicio');

        Schema::create('centros_costo', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('padre_id')->nullable()->constrained('centros_costo')->restrictOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('centros_costo', 'codigo', 'uq_centros_costo_codigo', 'VARCHAR(30)');

        Schema::create('impuestos', function (Blueprint $tabla) {
            $tabla->id();
            // IVA16, RETISR, ...
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            EsquemaErp::cantidad($tabla, 'tasa');
            $tabla->string('tipo', 20);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('impuestos', 'codigo', 'uq_impuestos_codigo', 'VARCHAR(30)');
        EsquemaErp::check('impuestos', 'chk_impuestos_tasa', 'tasa >= 0');
        EsquemaErp::check('impuestos', 'chk_impuestos_tipo', "tipo IN ('trasladado','retenido','otro')");

        Schema::create('tipos_cambio', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->char('moneda_origen', 3);
            $tabla->char('moneda_destino', 3);
            $tabla->date('fecha');
            EsquemaErp::cantidad($tabla, 'tasa');
            $tabla->timestamps();

            $tabla->unique(['moneda_origen', 'moneda_destino', 'fecha'], 'uq_tipos_cambio_par_fecha');
        });

        EsquemaErp::check('tipos_cambio', 'chk_tipos_cambio_tasa', 'tasa > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cambio');
        Schema::dropIfExists('impuestos');
        Schema::dropIfExists('centros_costo');
        Schema::dropIfExists('periodos_fiscales');
        Schema::dropIfExists('catalogo_cuentas');
    }
};
