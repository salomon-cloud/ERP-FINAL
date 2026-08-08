<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizaciones y pedidos, con sus lineas.
 *
 * precio_unitario, tasa_impuesto y los totales son FOTOS tomadas al crear la
 * linea: cambiar despues la lista de precios jamas reescribe un documento ya
 * emitido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_cotizacion', 30);
            $tabla->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();
            $tabla->foreignId('lista_precio_id')->nullable()->constrained('listas_precios')->nullOnDelete();
            $tabla->date('fecha');
            $tabla->date('vigencia')->nullable();
            $tabla->string('estado', 20)->default('borrador');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_descuento')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->text('notas')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_cotizacion', 'uq_cotizaciones_numero');
            $tabla->index(['cliente_id', 'fecha'], 'idx_cotizaciones_cliente');
            $tabla->index('estado', 'idx_cotizaciones_estado');
        });

        EsquemaErp::check('cotizaciones', 'chk_cotizaciones_estado', "estado IN ('borrador','enviada','aceptada','rechazada','convertida','cancelada')");
        EsquemaErp::check('cotizaciones', 'chk_cotizaciones_totales', 'subtotal >= 0 AND total_descuento >= 0 AND total_impuesto >= 0 AND total >= 0');
        EsquemaErp::check('cotizaciones', 'chk_cotizaciones_vigencia', 'vigencia IS NULL OR vigencia >= fecha');

        Schema::create('cotizacion_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->string('descripcion', 300)->nullable();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::dinero($tabla, 'precio_unitario');
            $tabla->decimal('porcentaje_descuento', 5, 2)->default(0);
            EsquemaErp::dinero($tabla, 'monto_descuento')->default(0);
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('cotizacion_id', 'idx_cotizacion_lineas_cotizacion');
        });

        EsquemaErp::check('cotizacion_lineas', 'chk_cotizacion_linea_valores', 'cantidad > 0 AND precio_unitario >= 0 AND porcentaje_descuento BETWEEN 0 AND 100 AND monto_descuento >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');
        // El descuento se expresa en porcentaje o en monto, nunca en los dos.
        EsquemaErp::check('cotizacion_lineas', 'chk_cotizacion_linea_descuento', 'monto_descuento = 0 OR porcentaje_descuento = 0');

        Schema::create('pedidos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_pedido', 30);
            $tabla->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $tabla->foreignId('lista_precio_id')->nullable()->constrained('listas_precios')->nullOnDelete();
            $tabla->date('fecha');
            $tabla->date('fecha_entrega')->nullable();
            $tabla->string('estado', 30)->default('borrador');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_descuento')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->text('notas')->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_pedido', 'uq_pedidos_numero');
            $tabla->index(['cliente_id', 'fecha'], 'idx_pedidos_cliente');
            $tabla->index('estado', 'idx_pedidos_estado');
        });

        EsquemaErp::check('pedidos', 'chk_pedidos_estado', "estado IN ('borrador','confirmado','surtido','facturado_parcial','facturado','cancelado')");
        EsquemaErp::check('pedidos', 'chk_pedidos_totales', 'subtotal >= 0 AND total_descuento >= 0 AND total_impuesto >= 0 AND total >= 0');
        EsquemaErp::check('pedidos', 'chk_pedidos_fechas', 'fecha_entrega IS NULL OR fecha_entrega >= fecha');

        Schema::create('pedido_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $tabla->string('descripcion', 300)->nullable();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::cantidad($tabla, 'cantidad_surtida')->default(0);
            EsquemaErp::dinero($tabla, 'precio_unitario');
            $tabla->decimal('porcentaje_descuento', 5, 2)->default(0);
            EsquemaErp::dinero($tabla, 'monto_descuento')->default(0);
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('pedido_id', 'idx_pedido_lineas_pedido');
            $tabla->index('producto_id', 'idx_pedido_lineas_producto');
        });

        EsquemaErp::check('pedido_lineas', 'chk_pedido_linea_valores', 'cantidad > 0 AND cantidad_surtida >= 0 AND precio_unitario >= 0 AND porcentaje_descuento BETWEEN 0 AND 100 AND monto_descuento >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');
        EsquemaErp::check('pedido_lineas', 'chk_pedido_linea_surtido', 'cantidad_surtida <= cantidad');
        EsquemaErp::check('pedido_lineas', 'chk_pedido_linea_descuento', 'monto_descuento = 0 OR porcentaje_descuento = 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_lineas');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('cotizacion_lineas');
        Schema::dropIfExists('cotizaciones');
    }
};
