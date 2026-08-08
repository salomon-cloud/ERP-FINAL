<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos maestros de Inventario: almacenes, ubicaciones, categorias, unidades,
 * productos y codigos de barras.
 *
 * El catalogo de productos es propiedad de Inventario. Ventas y Compras lo
 * referencian; jamas lo duplican.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            $tabla->text('direccion')->nullable();
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('almacenes', 'codigo', 'uq_almacenes_codigo', 'VARCHAR(30)');

        Schema::create('ubicaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            $tabla->boolean('es_surtible')->default(true);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['almacen_id', 'codigo'], 'uq_ubicaciones_almacen_codigo');
        });

        Schema::create('categorias_producto', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('padre_id')->nullable()->constrained('categorias_producto')->restrictOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('categorias_producto', 'codigo', 'uq_categorias_producto_codigo', 'VARCHAR(30)');

        Schema::create('unidades_medida', function (Blueprint $tabla) {
            $tabla->id();
            // PZA, KG, L, M
            $tabla->string('codigo', 10);
            $tabla->string('nombre', 100);
            EsquemaErp::cantidad($tabla, 'factor_base')->default(1);
            $tabla->boolean('es_base')->default(false);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('codigo', 'uq_unidades_codigo');
        });

        EsquemaErp::check('unidades_medida', 'chk_unidades_factor', 'factor_base > 0');

        Schema::create('productos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('sku', 50);
            $tabla->string('nombre', 200);
            $tabla->text('descripcion')->nullable();
            $tabla->foreignId('categoria_id')->nullable()->constrained('categorias_producto')->nullOnDelete();
            $tabla->foreignId('unidad_id')->nullable()->constrained('unidades_medida')->restrictOnDelete();
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::dinero($tabla, 'costo')->default(0);
            EsquemaErp::dinero($tabla, 'precio_venta')->default(0);
            EsquemaErp::cantidad($tabla, 'stock_minimo')->default(0);
            EsquemaErp::cantidad($tabla, 'stock_maximo')->default(0);
            $tabla->boolean('es_vendible')->default(true);
            $tabla->boolean('es_comprable')->default(true);
            // Los servicios no manejan existencia.
            $tabla->boolean('es_inventariable')->default(true);
            $tabla->boolean('rastrea_serie')->default(false);
            $tabla->string('estado', 20)->default('activo');
            EsquemaErp::auditoria($tabla);

            $tabla->index('categoria_id', 'idx_productos_categoria');
            $tabla->index('estado', 'idx_productos_estado');
        });

        EsquemaErp::unicoActivo('productos', 'sku', 'uq_productos_sku', 'VARCHAR(50)');
        EsquemaErp::check('productos', 'chk_productos_estado', "estado IN ('activo','inactivo')");
        EsquemaErp::check('productos', 'chk_productos_precios', 'costo >= 0 AND precio_venta >= 0');
        EsquemaErp::check('productos', 'chk_productos_stock', 'stock_minimo >= 0 AND stock_maximo >= 0');

        Schema::create('codigos_barras', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $tabla->string('codigo', 80);
            $tabla->boolean('es_principal')->default(false);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->unique('codigo', 'uq_codigos_barras_codigo');
            $tabla->index('producto_id', 'idx_codigos_barras_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_barras');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('unidades_medida');
        Schema::dropIfExists('categorias_producto');
        Schema::dropIfExists('ubicaciones');
        Schema::dropIfExists('almacenes');
    }
};
