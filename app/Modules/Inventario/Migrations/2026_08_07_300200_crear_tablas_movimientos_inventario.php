<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * movimientos_inventario ES el libro mayor del almacen.
 *
 * En todo el esquema no existe una columna "existencia": la existencia se
 * deriva con v_existencias a partir de estas filas, asi que nunca puede
 * desviarse en silencio de su historia. Cancelar un movimiento genera el
 * movimiento contrario; jamas se borra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('numero_lote', 60);
            $tabla->date('fecha_caducidad')->nullable();
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['producto_id', 'numero_lote'], 'uq_lotes_producto_numero');
            $tabla->index('fecha_caducidad', 'idx_lotes_caducidad');
        });

        Schema::create('numeros_serie', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('numero_serie', 60);
            $tabla->string('estado', 20)->default('en_stock');
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['producto_id', 'numero_serie'], 'uq_series_producto_numero');
        });

        EsquemaErp::check('numeros_serie', 'chk_series_estado', "estado IN ('en_stock','vendido','garantia','devuelto','baja')");

        Schema::create('movimientos_inventario', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $tabla->string('tipo_movimiento', 30);
            // Con signo, en unidad base: positivo = entrada, negativo = salida.
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::cantidad($tabla, 'costo_unitario')->default(0);
            $tabla->string('origen_tipo', 100)->nullable();
            $tabla->unsignedBigInteger('origen_id')->nullable();
            $tabla->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $tabla->foreignId('numero_serie_id')->nullable()->constrained('numeros_serie')->nullOnDelete();
            $tabla->string('estado', 20)->default('aplicado');
            $tabla->timestamp('aplicado_en')->useCurrent();
            $tabla->foreignId('aplicado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamps();
            $tabla->softDeletes();

            $tabla->index(['producto_id', 'aplicado_en'], 'idx_mov_inv_producto');
            $tabla->index(['almacen_id', 'ubicacion_id'], 'idx_mov_inv_almacen');
            $tabla->index('tipo_movimiento', 'idx_mov_inv_tipo');
            $tabla->index(['origen_tipo', 'origen_id'], 'idx_mov_inv_origen');
        });

        EsquemaErp::check('movimientos_inventario', 'chk_mov_inv_tipo', "tipo_movimiento IN ('compra','venta','traspaso_entrada','traspaso_salida','ajuste_entrada','ajuste_salida','devolucion_entrada','devolucion_salida','conteo','inicial','apartado','liberacion_apartado')");
        EsquemaErp::check('movimientos_inventario', 'chk_mov_inv_estado', "estado IN ('aplicado','cancelado')");
        EsquemaErp::check('movimientos_inventario', 'chk_mov_inv_cantidad', 'cantidad <> 0');
        EsquemaErp::check('movimientos_inventario', 'chk_mov_inv_costo', 'costo_unitario >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('numeros_serie');
        Schema::dropIfExists('lotes');
    }
};
