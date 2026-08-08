<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;

/**
 * Vistas de reporte de Finanzas.
 *
 * Los estados financieros son CONSULTAS sobre las polizas, asi que cuadran con
 * la contabilidad por construccion. No existe ninguna tabla desnormalizada de
 * reportes y ningun reporte se captura a mano.
 *
 * Corre con marca 9xxxxx, despues de las tablas de todos los modulos, porque
 * una vista solo puede crearse cuando ya existe todo lo que lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Una fila por linea de poliza contabilizada, con el saldo corrido por cuenta.
        EsquemaErp::crearVista('v_libro_mayor', <<<'SQL'
            SELECT
                pl.id               AS linea_id,
                p.id                AS poliza_id,
                p.numero_poliza,
                p.fecha,
                p.concepto          AS concepto_poliza,
                p.origen_tipo,
                p.origen_id,
                pl.periodo_fiscal_id,
                pf.nombre           AS periodo,
                pf.ejercicio,
                c.id                AS cuenta_id,
                c.codigo            AS cuenta_codigo,
                c.nombre            AS cuenta_nombre,
                c.tipo_cuenta,
                c.naturaleza,
                pl.centro_costo_id,
                cc.codigo           AS centro_costo_codigo,
                cc.nombre           AS centro_costo_nombre,
                pl.concepto         AS concepto_linea,
                pl.debe,
                pl.haber,
                pl.debe - pl.haber  AS movimiento,
                SUM(pl.debe - pl.haber) OVER (
                    PARTITION BY pl.cuenta_id
                    ORDER BY p.fecha, p.id, pl.id
                )                   AS saldo_corrido
            FROM poliza_lineas pl
            JOIN polizas p              ON p.id  = pl.poliza_id
            JOIN catalogo_cuentas c     ON c.id  = pl.cuenta_id
            JOIN periodos_fiscales pf   ON pf.id = pl.periodo_fiscal_id
            LEFT JOIN centros_costo cc  ON cc.id = pl.centro_costo_id
            WHERE p.estado = 'contabilizada'
              AND p.deleted_at IS NULL
            SQL);

        // Prueba de cuadre: SUM(total_debe) debe ser igual a SUM(total_haber).
        EsquemaErp::crearVista('v_balanza_comprobacion', <<<'SQL'
            SELECT
                pf.id        AS periodo_fiscal_id,
                pf.nombre    AS periodo,
                pf.ejercicio,
                pf.fecha_inicio,
                pf.fecha_fin,
                c.id         AS cuenta_id,
                c.codigo     AS cuenta_codigo,
                c.nombre     AS cuenta_nombre,
                c.tipo_cuenta,
                c.naturaleza,
                SUM(pl.debe)            AS total_debe,
                SUM(pl.haber)           AS total_haber,
                SUM(pl.debe - pl.haber) AS saldo,
                CASE WHEN c.naturaleza = 'acreedora'
                     THEN SUM(pl.haber - pl.debe)
                     ELSE SUM(pl.debe - pl.haber)
                END                     AS saldo_natural
            FROM poliza_lineas pl
            JOIN polizas p            ON p.id  = pl.poliza_id
            JOIN catalogo_cuentas c   ON c.id  = pl.cuenta_id
            JOIN periodos_fiscales pf ON pf.id = pl.periodo_fiscal_id
            WHERE p.estado = 'contabilizada'
              AND p.deleted_at IS NULL
            GROUP BY pf.id, pf.nombre, pf.ejercicio, pf.fecha_inicio, pf.fecha_fin,
                     c.id, c.codigo, c.nombre, c.tipo_cuenta, c.naturaleza
            SQL);

        // Saldo ACUMULADO al cierre de cada periodo: filtra por periodo_fiscal_id
        // para leer el balance "al" ese periodo.
        EsquemaErp::crearVista('v_balance_general', <<<'SQL'
            SELECT
                b.periodo_fiscal_id,
                b.periodo,
                b.ejercicio,
                b.fecha_fin,
                b.cuenta_id,
                b.cuenta_codigo,
                b.cuenta_nombre,
                b.tipo_cuenta,
                CASE
                    WHEN b.tipo_cuenta IN ('activo','activo_contra') THEN 'activo'
                    WHEN b.tipo_cuenta IN ('pasivo','pasivo_contra') THEN 'pasivo'
                    ELSE 'capital'
                END AS seccion,
                b.saldo_natural AS movimiento_periodo,
                SUM(b.saldo_natural) OVER (
                    PARTITION BY b.cuenta_id
                    ORDER BY b.fecha_fin, b.periodo_fiscal_id
                ) AS saldo_final
            FROM v_balanza_comprobacion b
            WHERE b.tipo_cuenta IN ('activo','pasivo','capital',
                                    'activo_contra','pasivo_contra','capital_contra')
            SQL);

        // monto es positivo en las dos secciones; resultado = ingresos - egresos.
        EsquemaErp::crearVista('v_estado_resultados', <<<'SQL'
            SELECT
                pf.id     AS periodo_fiscal_id,
                pf.nombre AS periodo,
                pf.ejercicio,
                pl.centro_costo_id,
                cc.codigo AS centro_costo_codigo,
                c.id      AS cuenta_id,
                c.codigo  AS cuenta_codigo,
                c.nombre  AS cuenta_nombre,
                c.tipo_cuenta,
                CASE WHEN c.tipo_cuenta IN ('ingreso','ingreso_contra')
                     THEN 'ingreso' ELSE 'egreso'
                END AS seccion,
                CASE WHEN c.tipo_cuenta IN ('ingreso','ingreso_contra')
                     THEN SUM(pl.haber - pl.debe)
                     ELSE SUM(pl.debe - pl.haber)
                END AS monto
            FROM poliza_lineas pl
            JOIN polizas p            ON p.id  = pl.poliza_id
            JOIN catalogo_cuentas c   ON c.id  = pl.cuenta_id
            JOIN periodos_fiscales pf ON pf.id = pl.periodo_fiscal_id
            LEFT JOIN centros_costo cc ON cc.id = pl.centro_costo_id
            WHERE p.estado = 'contabilizada'
              AND p.deleted_at IS NULL
              AND c.tipo_cuenta IN ('ingreso','egreso','ingreso_contra','egreso_contra')
            GROUP BY pf.id, pf.nombre, pf.ejercicio, pl.centro_costo_id, cc.codigo,
                     c.id, c.codigo, c.nombre, c.tipo_cuenta
            SQL);

        // Solo los movimientos que tocan una cuenta de efectivo, clasificados
        // por el origen de la poliza.
        EsquemaErp::crearVista('v_flujo_efectivo', <<<'SQL'
            SELECT
                pf.id     AS periodo_fiscal_id,
                pf.nombre AS periodo,
                pf.ejercicio,
                p.fecha,
                p.numero_poliza,
                p.origen_tipo,
                c.id      AS cuenta_id,
                c.codigo  AS cuenta_codigo,
                c.nombre  AS cuenta_nombre,
                CASE
                    WHEN p.origen_tipo IN ('factura','nota_credito','cobro','factura_proveedor',
                                           'devolucion_compra','nomina') THEN 'operacion'
                    WHEN p.origen_tipo IN ('cierre','ajuste')            THEN 'financiamiento'
                    ELSE 'inversion'
                END AS tipo_flujo,
                pl.debe  AS entrada,
                pl.haber AS salida,
                pl.debe - pl.haber AS flujo_neto
            FROM poliza_lineas pl
            JOIN polizas p            ON p.id  = pl.poliza_id
            JOIN catalogo_cuentas c   ON c.id  = pl.cuenta_id
            JOIN periodos_fiscales pf ON pf.id = pl.periodo_fiscal_id
            WHERE p.estado = 'contabilizada'
              AND p.deleted_at IS NULL
              AND c.es_efectivo = TRUE
            SQL);

        // IVA trasladado (facturas de venta) e IVA acreditable (de proveedor).
        EsquemaErp::crearVista('v_reporte_impuestos', <<<'SQL'
            SELECT
                'trasladado'    AS naturaleza,
                f.periodo_fiscal_id,
                f.fecha_emision AS fecha_documento,
                i.id            AS impuesto_id,
                i.codigo        AS impuesto_codigo,
                i.nombre        AS impuesto_nombre,
                i.tasa          AS impuesto_tasa,
                fl.subtotal     AS base_gravable,
                fl.monto_impuesto,
                f.id            AS documento_id,
                f.numero_factura AS documento_numero
            FROM factura_lineas fl
            JOIN facturas f  ON f.id = fl.factura_id
            JOIN impuestos i ON i.id = fl.impuesto_id
            WHERE f.estado <> 'cancelada'
              AND f.deleted_at IS NULL
            UNION ALL
            SELECT
                'acreditable'   AS naturaleza,
                fp.periodo_fiscal_id,
                fp.fecha        AS fecha_documento,
                i.id            AS impuesto_id,
                i.codigo        AS impuesto_codigo,
                i.nombre        AS impuesto_nombre,
                i.tasa          AS impuesto_tasa,
                fpl.subtotal    AS base_gravable,
                fpl.monto_impuesto,
                fp.id           AS documento_id,
                fp.numero_factura AS documento_numero
            FROM factura_proveedor_lineas fpl
            JOIN facturas_proveedor fp ON fp.id = fpl.factura_proveedor_id
            JOIN impuestos i           ON i.id  = fpl.impuesto_id
            WHERE fp.estado <> 'cancelada'
              AND fp.deleted_at IS NULL
            SQL);

        // Presupuesto contra lo realmente contabilizado en la misma cuenta y
        // periodo. variacion > 0 significa que se gasto menos de lo presupuestado.
        EsquemaErp::crearVista('v_presupuesto_vs_real', <<<'SQL'
            SELECT
                pr.id     AS presupuesto_id,
                pr.nombre AS presupuesto_nombre,
                pr.periodo_fiscal_id,
                pr.centro_costo_id,
                prl.cuenta_id,
                c.codigo  AS cuenta_codigo,
                c.nombre  AS cuenta_nombre,
                prl.mes,
                prl.monto_proyectado,
                COALESCE((
                    SELECT SUM(pl.debe - pl.haber)
                    FROM poliza_lineas pl
                    JOIN polizas p ON p.id = pl.poliza_id
                    WHERE pl.cuenta_id = prl.cuenta_id
                      AND pl.periodo_fiscal_id = pr.periodo_fiscal_id
                      AND p.estado = 'contabilizada'
                      AND p.deleted_at IS NULL
                ), 0) AS monto_real,
                prl.monto_proyectado - COALESCE((
                    SELECT SUM(pl.debe - pl.haber)
                    FROM poliza_lineas pl
                    JOIN polizas p ON p.id = pl.poliza_id
                    WHERE pl.cuenta_id = prl.cuenta_id
                      AND pl.periodo_fiscal_id = pr.periodo_fiscal_id
                      AND p.estado = 'contabilizada'
                      AND p.deleted_at IS NULL
                ), 0) AS variacion
            FROM presupuesto_lineas prl
            JOIN presupuestos pr    ON pr.id = prl.presupuesto_id
            JOIN catalogo_cuentas c ON c.id  = prl.cuenta_id
            WHERE pr.deleted_at IS NULL
            SQL);
    }

    public function down(): void
    {
        EsquemaErp::eliminarVista('v_presupuesto_vs_real');
        EsquemaErp::eliminarVista('v_reporte_impuestos');
        EsquemaErp::eliminarVista('v_flujo_efectivo');
        EsquemaErp::eliminarVista('v_estado_resultados');
        EsquemaErp::eliminarVista('v_balance_general');
        EsquemaErp::eliminarVista('v_balanza_comprobacion');
        EsquemaErp::eliminarVista('v_libro_mayor');
    }
};
