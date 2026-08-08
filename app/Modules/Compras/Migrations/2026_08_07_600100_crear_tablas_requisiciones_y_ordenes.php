<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * proveedores, requisiciones y ordenes de compra: el espejo de Ventas del lado
 * de la compra.
 *
 * requisiciones.departamento_id apunta a la tabla de RH, por eso Compras migra
 * despues de RH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('codigo', 30);
            $tabla->string('nombre', 200);
            $tabla->string('razon_social', 200)->nullable();
            $tabla->string('rfc', 30)->nullable();
            $tabla->string('contacto', 150)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->text('direccion')->nullable();
            $tabla->foreignId('condicion_pago_id')->nullable()->constrained('catalogos')->nullOnDelete();
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->string('estado', 20)->default('activo');
            EsquemaErp::auditoria($tabla);

            $tabla->index('estado', 'idx_proveedores_estado');
        });

        EsquemaErp::unicoActivo('proveedores', 'codigo', 'uq_proveedores_codigo', 'VARCHAR(30)');
        EsquemaErp::check('proveedores', 'chk_proveedores_estado', "estado IN ('activo','inactivo')");

        Schema::create('requisiciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_requisicion', 30);
            $tabla->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $tabla->foreignId('solicitante_id')->constrained('users')->restrictOnDelete();
            $tabla->date('fecha_requerida')->nullable();
            $tabla->string('estado', 20)->default('borrador');
            $tabla->text('notas')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_requisicion', 'uq_requisiciones_numero');
            $tabla->index('estado', 'idx_requisiciones_estado');
        });

        EsquemaErp::check('requisiciones', 'chk_requisiciones_estado', "estado IN ('borrador','enviada','aprobada','rechazada','convertida','cerrada')");

        Schema::create('requisicion_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('requisicion_id')->constrained('requisiciones')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad_solicitada');
            $tabla->foreignId('proveedor_sugerido_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $tabla->text('notas')->nullable();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('requisicion_id', 'idx_requisicion_lineas_requisicion');
        });

        EsquemaErp::check('requisicion_lineas', 'chk_requisicion_linea_cantidad', 'cantidad_solicitada > 0');

        Schema::create('ordenes_compra', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_orden', 30);
            $tabla->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $tabla->foreignId('requisicion_id')->nullable()->constrained('requisiciones')->nullOnDelete();
            $tabla->date('fecha');
            $tabla->date('fecha_entrega')->nullable();
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->string('estado', 30)->default('borrador');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_descuento')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->text('notas')->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_orden', 'uq_ordenes_compra_numero');
            $tabla->index(['proveedor_id', 'fecha'], 'idx_ordenes_compra_proveedor');
            $tabla->index('estado', 'idx_ordenes_compra_estado');
        });

        EsquemaErp::check('ordenes_compra', 'chk_ordenes_compra_estado', "estado IN ('borrador','enviada','confirmada','recibida','recibida_parcial','facturada','cancelada')");
        EsquemaErp::check('ordenes_compra', 'chk_ordenes_compra_totales', 'subtotal >= 0 AND total_descuento >= 0 AND total_impuesto >= 0 AND total >= 0');
        EsquemaErp::check('ordenes_compra', 'chk_ordenes_compra_fechas', 'fecha_entrega IS NULL OR fecha_entrega >= fecha');

        Schema::create('orden_compra_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $tabla->string('descripcion', 300)->nullable();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::cantidad($tabla, 'cantidad_recibida')->default(0);
            EsquemaErp::dinero($tabla, 'costo_unitario');
            $tabla->decimal('porcentaje_descuento', 5, 2)->default(0);
            EsquemaErp::dinero($tabla, 'monto_descuento')->default(0);
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('orden_compra_id', 'idx_orden_compra_lineas_orden');
            $tabla->index('producto_id', 'idx_orden_compra_lineas_producto');
        });

        EsquemaErp::check('orden_compra_lineas', 'chk_orden_compra_linea_valores', 'cantidad > 0 AND cantidad_recibida >= 0 AND costo_unitario >= 0 AND porcentaje_descuento BETWEEN 0 AND 100 AND monto_descuento >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');
        EsquemaErp::check('orden_compra_lineas', 'chk_orden_compra_linea_recibida', 'cantidad_recibida <= cantidad');
        EsquemaErp::check('orden_compra_lineas', 'chk_orden_compra_linea_descuento', 'monto_descuento = 0 OR porcentaje_descuento = 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_lineas');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('requisicion_lineas');
        Schema::dropIfExists('requisiciones');
        Schema::dropIfExists('proveedores');
    }
};
