<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * recepciones, facturas de proveedor, devoluciones y pagos.
 *
 * Recibir aplica movimientos de entrada al inventario; facturar contabiliza
 * cuentas por pagar + gasto + IVA acreditable, con el cotejo de tres vias
 * orden <-> recepcion <-> factura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_recepcion', 30);
            $tabla->foreignId('orden_compra_id')->constrained('ordenes_compra')->restrictOnDelete();
            $tabla->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $tabla->date('fecha');
            $tabla->string('estado', 20)->default('borrador');
            $tabla->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_recepcion', 'uq_recepciones_numero');
            $tabla->index('orden_compra_id', 'idx_recepciones_orden');
        });

        EsquemaErp::check('recepciones', 'chk_recepciones_estado', "estado IN ('borrador','aplicada','cancelada')");

        Schema::create('recepcion_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('recepcion_id')->constrained('recepciones')->cascadeOnDelete();
            $tabla->foreignId('orden_compra_linea_id')->constrained('orden_compra_lineas')->restrictOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad_recibida');
            // Foto del costo de la orden de compra.
            EsquemaErp::dinero($tabla, 'costo_unitario');
            $tabla->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $tabla->foreignId('numero_serie_id')->nullable()->constrained('numeros_serie')->nullOnDelete();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('recepcion_id', 'idx_recepcion_lineas_recepcion');
        });

        EsquemaErp::check('recepcion_lineas', 'chk_recepcion_linea_valores', 'cantidad_recibida > 0 AND costo_unitario >= 0');

        Schema::create('facturas_proveedor', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_factura', 30);
            $tabla->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $tabla->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra')->nullOnDelete();
            $tabla->foreignId('recepcion_id')->nullable()->constrained('recepciones')->nullOnDelete();
            $tabla->foreignId('periodo_fiscal_id')->nullable()->constrained('periodos_fiscales')->restrictOnDelete();
            $tabla->date('fecha');
            $tabla->date('fecha_vencimiento')->nullable();
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->string('estado', 30)->default('borrador');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            EsquemaErp::dinero($tabla, 'total_pagado')->default(0);
            $tabla->text('notas')->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_factura', 'uq_facturas_proveedor_numero');
            $tabla->index(['proveedor_id', 'fecha'], 'idx_facturas_proveedor_proveedor');
            $tabla->index('estado', 'idx_facturas_proveedor_estado');
        });

        EsquemaErp::check('facturas_proveedor', 'chk_facturas_proveedor_estado', "estado IN ('borrador','contabilizada','pagada_parcial','pagada','cancelada')");
        EsquemaErp::check('facturas_proveedor', 'chk_facturas_proveedor_totales', 'subtotal >= 0 AND total_impuesto >= 0 AND total >= 0 AND total_pagado >= 0');
        EsquemaErp::check('facturas_proveedor', 'chk_facturas_proveedor_pagado', 'total_pagado <= total');
        EsquemaErp::check('facturas_proveedor', 'chk_facturas_proveedor_vencimiento', 'fecha_vencimiento IS NULL OR fecha_vencimiento >= fecha');

        Schema::create('factura_proveedor_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('factura_proveedor_id')->constrained('facturas_proveedor')->cascadeOnDelete();
            $tabla->foreignId('orden_compra_linea_id')->nullable()->constrained('orden_compra_lineas')->nullOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->string('descripcion', 300)->nullable();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::dinero($tabla, 'costo_unitario');
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('factura_proveedor_id', 'idx_factura_proveedor_lineas_factura');
        });

        EsquemaErp::check('factura_proveedor_lineas', 'chk_factura_proveedor_linea_valores', 'cantidad > 0 AND costo_unitario >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');

        Schema::create('devoluciones_compra', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_devolucion', 30);
            $tabla->foreignId('factura_proveedor_id')->constrained('facturas_proveedor')->restrictOnDelete();
            $tabla->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $tabla->string('motivo', 30);
            $tabla->string('estado', 20)->default('borrador');
            $tabla->date('fecha');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_devolucion', 'uq_devoluciones_compra_numero');
            $tabla->index('factura_proveedor_id', 'idx_devoluciones_compra_factura');
        });

        EsquemaErp::check('devoluciones_compra', 'chk_devoluciones_compra_motivo', "motivo IN ('defectuoso','equivocado','excedente','otro')");
        EsquemaErp::check('devoluciones_compra', 'chk_devoluciones_compra_estado', "estado IN ('borrador','aplicada','cancelada')");
        EsquemaErp::check('devoluciones_compra', 'chk_devoluciones_compra_totales', 'subtotal >= 0 AND total_impuesto >= 0 AND total >= 0');

        Schema::create('devolucion_compra_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('devolucion_id')->constrained('devoluciones_compra')->cascadeOnDelete();
            $tabla->foreignId('factura_proveedor_linea_id')->nullable()->constrained('factura_proveedor_lineas')->nullOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::dinero($tabla, 'costo_unitario');
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('devolucion_id', 'idx_devolucion_compra_lineas_devolucion');
        });

        EsquemaErp::check('devolucion_compra_lineas', 'chk_devolucion_compra_linea_valores', 'cantidad > 0 AND costo_unitario >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');

        Schema::create('pagos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_pago', 30);
            $tabla->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $tabla->foreignId('factura_proveedor_id')->nullable()->constrained('facturas_proveedor')->nullOnDelete();
            $tabla->date('fecha');
            EsquemaErp::dinero($tabla, 'monto');
            $tabla->string('forma_pago', 30)->default('transferencia');
            $tabla->string('referencia', 120)->nullable();
            $tabla->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->nullOnDelete();
            $tabla->string('estado', 20)->default('borrador');
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_pago', 'uq_pagos_numero');
            $tabla->index(['proveedor_id', 'fecha'], 'idx_pagos_proveedor');
            $tabla->index('factura_proveedor_id', 'idx_pagos_factura');
        });

        EsquemaErp::check('pagos', 'chk_pagos_monto', 'monto > 0');
        EsquemaErp::check('pagos', 'chk_pagos_forma', "forma_pago IN ('efectivo','transferencia','cheque')");
        EsquemaErp::check('pagos', 'chk_pagos_estado', "estado IN ('borrador','aplicado','cancelado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('devolucion_compra_lineas');
        Schema::dropIfExists('devoluciones_compra');
        Schema::dropIfExists('factura_proveedor_lineas');
        Schema::dropIfExists('facturas_proveedor');
        Schema::dropIfExists('recepcion_lineas');
        Schema::dropIfExists('recepciones');
    }
};
