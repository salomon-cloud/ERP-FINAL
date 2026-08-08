<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * facturas, notas_credito y cobros, con sus lineas.
 *
 * Emitir una factura contabiliza ingreso + impuesto + cuentas por cobrar en
 * Finanzas. El estado 'vencida' lo fija el servicio a partir de la fecha de
 * vencimiento y lo cobrado; no es un dato que alguien tenga que recordar
 * refrescar a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_factura', 30);
            $tabla->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $tabla->foreignId('periodo_fiscal_id')->nullable()->constrained('periodos_fiscales')->restrictOnDelete();
            $tabla->foreignId('factura_electronica_id')->nullable()->constrained('facturas_electronicas')->nullOnDelete();
            $tabla->date('fecha_emision');
            $tabla->date('fecha_vencimiento')->nullable();
            $tabla->foreignId('condicion_pago_id')->nullable()->constrained('catalogos')->nullOnDelete();
            $tabla->string('estado', 30)->default('borrador');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_descuento')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            EsquemaErp::dinero($tabla, 'total_cobrado')->default(0);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->text('notas')->nullable();
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_factura', 'uq_facturas_numero');
            $tabla->index(['cliente_id', 'fecha_emision'], 'idx_facturas_cliente');
            $tabla->index('estado', 'idx_facturas_estado');
            $tabla->index('periodo_fiscal_id', 'idx_facturas_periodo');
        });

        EsquemaErp::check('facturas', 'chk_facturas_estado', "estado IN ('borrador','emitida','cobrada_parcial','cobrada','vencida','cancelada')");
        EsquemaErp::check('facturas', 'chk_facturas_totales', 'subtotal >= 0 AND total_descuento >= 0 AND total_impuesto >= 0 AND total >= 0 AND total_cobrado >= 0');
        EsquemaErp::check('facturas', 'chk_facturas_cobrado', 'total_cobrado <= total');
        EsquemaErp::check('facturas', 'chk_facturas_vencimiento', 'fecha_vencimiento IS NULL OR fecha_vencimiento >= fecha_emision');

        Schema::create('factura_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $tabla->foreignId('pedido_linea_id')->nullable()->constrained('pedido_lineas')->nullOnDelete();
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

            $tabla->index('factura_id', 'idx_factura_lineas_factura');
        });

        EsquemaErp::check('factura_lineas', 'chk_factura_linea_valores', 'cantidad > 0 AND precio_unitario >= 0 AND porcentaje_descuento BETWEEN 0 AND 100 AND monto_descuento >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');
        EsquemaErp::check('factura_lineas', 'chk_factura_linea_descuento', 'monto_descuento = 0 OR porcentaje_descuento = 0');

        Schema::create('notas_credito', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_nota', 30);
            $tabla->foreignId('factura_id')->constrained('facturas')->restrictOnDelete();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $tabla->string('motivo', 30);
            $tabla->string('estado', 20)->default('borrador');
            $tabla->date('fecha_emision');
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            EsquemaErp::versionFila($tabla);
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_nota', 'uq_notas_credito_numero');
            $tabla->index('factura_id', 'idx_notas_credito_factura');
        });

        EsquemaErp::check('notas_credito', 'chk_notas_credito_motivo', "motivo IN ('devolucion','descuento','error','otro')");
        EsquemaErp::check('notas_credito', 'chk_notas_credito_estado', "estado IN ('borrador','emitida','cancelada')");
        EsquemaErp::check('notas_credito', 'chk_notas_credito_totales', 'subtotal >= 0 AND total_impuesto >= 0 AND total >= 0');

        Schema::create('nota_credito_lineas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('nota_credito_id')->constrained('notas_credito')->cascadeOnDelete();
            $tabla->foreignId('factura_linea_id')->nullable()->constrained('factura_lineas')->nullOnDelete();
            $tabla->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $tabla->string('descripcion', 300)->nullable();
            EsquemaErp::cantidad($tabla, 'cantidad');
            EsquemaErp::dinero($tabla, 'precio_unitario');
            $tabla->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            EsquemaErp::cantidad($tabla, 'tasa_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'monto_impuesto')->default(0);
            EsquemaErp::dinero($tabla, 'subtotal')->default(0);
            EsquemaErp::dinero($tabla, 'total')->default(0);
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index('nota_credito_id', 'idx_nota_credito_lineas_nota');
        });

        EsquemaErp::check('nota_credito_lineas', 'chk_nota_credito_linea_valores', 'cantidad > 0 AND precio_unitario >= 0 AND monto_impuesto >= 0 AND subtotal >= 0 AND total >= 0');

        Schema::create('cobros', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('numero_cobro', 30);
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            // NULL cuando es un cobro a cuenta que se aplicara despues.
            $tabla->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            $tabla->date('fecha');
            EsquemaErp::dinero($tabla, 'monto');
            $tabla->string('forma_pago', 30)->default('efectivo');
            $tabla->string('referencia', 120)->nullable();
            $tabla->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->nullOnDelete();
            $tabla->string('estado', 20)->default('borrador');
            EsquemaErp::auditoria($tabla);

            $tabla->unique('numero_cobro', 'uq_cobros_numero');
            $tabla->index(['cliente_id', 'fecha'], 'idx_cobros_cliente');
            $tabla->index('factura_id', 'idx_cobros_factura');
        });

        EsquemaErp::check('cobros', 'chk_cobros_monto', 'monto > 0');
        EsquemaErp::check('cobros', 'chk_cobros_forma_pago', "forma_pago IN ('efectivo','transferencia','cheque','tarjeta','liga_pago')");
        EsquemaErp::check('cobros', 'chk_cobros_estado', "estado IN ('borrador','aplicado','cancelado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('nota_credito_lineas');
        Schema::dropIfExists('notas_credito');
        Schema::dropIfExists('factura_lineas');
        Schema::dropIfExists('facturas');
    }
};
