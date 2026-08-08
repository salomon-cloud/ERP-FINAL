<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listas de precios y clientes.
 *
 * Ventas es duena de `clientes`. CRM los referencia para oportunidades e
 * historial, y nunca guarda una segunda copia.
 *
 * Las listas van primero porque el cliente apunta a su lista por omision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas_precios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 150);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->boolean('es_predeterminada')->default(false);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('listas_precios', 'codigo', 'uq_listas_precios_codigo', 'VARCHAR(30)');

        Schema::create('lista_precio_items', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('lista_precio_id')->constrained('listas_precios')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad_minima')->default(1);
            EsquemaErp::dinero($tabla, 'precio');
            $tabla->timestamps();

            $tabla->unique(['lista_precio_id', 'producto_id', 'cantidad_minima'], 'uq_lista_precio_item');
            $tabla->index('producto_id', 'idx_lista_precio_items_producto');
        });

        EsquemaErp::check('lista_precio_items', 'chk_lista_precio_item_valores', 'cantidad_minima > 0 AND precio >= 0');

        Schema::create('clientes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 200);
            $tabla->string('razon_social', 200)->nullable();
            $tabla->string('rfc', 30)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->text('direccion')->nullable();
            EsquemaErp::dinero($tabla, 'limite_credito')->default(0);
            // Fila de catalogos con grupo 'condiciones_pago'
            $tabla->foreignId('condicion_pago_id')->nullable()->constrained('catalogos')->nullOnDelete();
            $tabla->foreignId('lista_precio_id')->nullable()->constrained('listas_precios')->nullOnDelete();
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->string('estado', 20)->default('activo');
            EsquemaErp::auditoria($tabla);

            $tabla->index('estado', 'idx_clientes_estado');
            $tabla->index('rfc', 'idx_clientes_rfc');
        });

        EsquemaErp::unicoActivo('clientes', 'codigo', 'uq_clientes_codigo', 'VARCHAR(30)');
        EsquemaErp::check('clientes', 'chk_clientes_estado', "estado IN ('activo','inactivo')");
        EsquemaErp::check('clientes', 'chk_clientes_limite_credito', 'limite_credito >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('lista_precio_items');
        Schema::dropIfExists('listas_precios');
    }
};
