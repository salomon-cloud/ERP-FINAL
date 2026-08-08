<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traspasos, ajustes, conteos fisicos y reglas de reorden.
 *
 * Son documentos: guardan la intencion y la autorizacion. Aplicarlos es lo que
 * escribe filas en movimientos_inventario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traspasos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_traspaso', 30);
            $tabla->foreignId('almacen_origen_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->foreignId('almacen_destino_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('aprobado_en')->nullable();
            $tabla->timestamp('enviado_en')->nullable();
            $tabla->timestamp('recibido_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_traspaso', 'uq_traspasos_numero');
            $tabla->index('estado', 'idx_traspasos_estado');
        });

        EsquemaErp::check('traspasos', 'chk_traspasos_estado', "estado IN ('borrador','en_transito','recibido','cancelado')");
        EsquemaErp::check('traspasos', 'chk_traspasos_almacenes', 'almacen_origen_id <> almacen_destino_id');

        Schema::create('traspaso_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('traspaso_id')->constrained('traspasos')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::cantidad($tabla, 'costo_unitario')->default(0);
            $tabla->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $tabla->timestamp('created_at')->useCurrent();
        });

        EsquemaErp::check('traspaso_lineas', 'chk_traspaso_linea_cantidad', 'cantidad > 0');

        Schema::create('ajustes_inventario', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_ajuste', 30);
            $tabla->string('motivo', 500);
            $tabla->string('estado', 20)->default('borrador');
            $tabla->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('aprobado_en')->nullable();
            $tabla->timestamp('aplicado_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_ajuste', 'uq_ajustes_numero');
            $tabla->index('estado', 'idx_ajustes_estado');
        });

        EsquemaErp::check('ajustes_inventario', 'chk_ajustes_estado', "estado IN ('borrador','aplicado','cancelado')");

        Schema::create('ajuste_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('ajuste_id')->constrained('ajustes_inventario')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            // Con signo: positivo sobra, negativo falta.
            EsquemaErp::cantidad($tabla, 'diferencia');
            EsquemaErp::cantidad($tabla, 'costo_unitario')->default(0);
            $tabla->string('motivo', 500)->nullable();
            $tabla->timestamp('created_at')->useCurrent();
        });

        EsquemaErp::check('ajuste_lineas', 'chk_ajuste_linea_diferencia', 'diferencia <> 0');

        Schema::create('conteos_inventario', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_conteo', 30);
            $tabla->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->foreignId('contado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('contado_en')->nullable();
            $tabla->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('cerrado_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_conteo', 'uq_conteos_numero');
            $tabla->index('estado', 'idx_conteos_estado');
        });

        EsquemaErp::check('conteos_inventario', 'chk_conteos_estado', "estado IN ('borrador','en_proceso','contado','ajustado','cerrado')");

        Schema::create('conteo_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('conteo_id')->constrained('conteos_inventario')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            // Tomada de v_existencias al iniciar el conteo.
            EsquemaErp::cantidad($tabla, 'cantidad_esperada')->default(0);
            EsquemaErp::cantidad($tabla, 'cantidad_contada')->nullable();
            EsquemaErp::cantidad($tabla, 'diferencia')->default(0);
            $tabla->timestamps();

            $tabla->unique(['conteo_id', 'producto_id'], 'uq_conteo_linea');
        });

        Schema::create('reglas_reorden', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $tabla->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad_minima')->default(0);
            EsquemaErp::cantidad($tabla, 'cantidad_maxima')->default(0);
            EsquemaErp::cantidad($tabla, 'cantidad_reorden')->default(0);
            $tabla->smallInteger('dias_entrega')->default(0);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['producto_id', 'almacen_id'], 'uq_regla_reorden');
            $tabla->index('almacen_id', 'idx_reglas_reorden_almacen');
        });

        EsquemaErp::check('reglas_reorden', 'chk_reorden_cantidades', 'cantidad_minima >= 0 AND cantidad_maxima >= 0 AND cantidad_reorden >= 0 AND dias_entrega >= 0');
        EsquemaErp::check('reglas_reorden', 'chk_reorden_min_max', 'cantidad_maxima = 0 OR cantidad_minima <= cantidad_maxima');
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_reorden');
        Schema::dropIfExists('conteo_lineas');
        Schema::dropIfExists('conteos_inventario');
        Schema::dropIfExists('ajuste_lineas');
        Schema::dropIfExists('ajustes_inventario');
        Schema::dropIfExists('traspaso_lineas');
        Schema::dropIfExists('traspasos');
    }
};
