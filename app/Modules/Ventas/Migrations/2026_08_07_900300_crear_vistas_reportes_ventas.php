<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;

/**
 * v_historial_cliente es la vista de 360 grados de un cliente. Ventas es dueno
 * de los datos y CRM los pinta en la ficha de la cuenta: los dos leen esta
 * misma definicion en vez de que cada uno arme su propia suma.
 */
return new class extends Migration
{
    public function up(): void
    {
        EsquemaErp::crearVista('v_historial_cliente', <<<'SQL'
            SELECT
                c.id     AS cliente_id,
                c.codigo AS cliente_codigo,
                c.nombre AS cliente_nombre,
                c.estado,
                (SELECT COUNT(*) FROM cotizaciones q
                  WHERE q.cliente_id = c.id AND q.deleted_at IS NULL)          AS cotizaciones,
                (SELECT COUNT(*) FROM pedidos pe
                  WHERE pe.cliente_id = c.id AND pe.deleted_at IS NULL)        AS pedidos,
                (SELECT COUNT(*) FROM facturas f
                  WHERE f.cliente_id = c.id AND f.deleted_at IS NULL)          AS facturas,
                (SELECT COALESCE(SUM(f.total), 0) FROM facturas f
                  WHERE f.cliente_id = c.id AND f.deleted_at IS NULL
                    AND f.estado <> 'cancelada')                               AS monto_facturado,
                (SELECT COALESCE(SUM(f.total_cobrado), 0) FROM facturas f
                  WHERE f.cliente_id = c.id AND f.deleted_at IS NULL
                    AND f.estado <> 'cancelada')                               AS monto_cobrado,
                (SELECT COALESCE(SUM(f.total - f.total_cobrado), 0) FROM facturas f
                  WHERE f.cliente_id = c.id AND f.deleted_at IS NULL
                    AND f.estado IN ('emitida','cobrada_parcial','vencida'))   AS saldo_pendiente,
                (SELECT COALESCE(SUM(n.total), 0) FROM notas_credito n
                  WHERE n.cliente_id = c.id AND n.deleted_at IS NULL
                    AND n.estado = 'emitida')                                  AS monto_acreditado,
                (SELECT MAX(f.fecha_emision) FROM facturas f
                  WHERE f.cliente_id = c.id AND f.deleted_at IS NULL)          AS ultima_factura,
                (SELECT MAX(co.fecha) FROM cobros co
                  WHERE co.cliente_id = c.id AND co.deleted_at IS NULL
                    AND co.estado = 'aplicado')                                AS ultimo_cobro,
                (SELECT COUNT(*) FROM oportunidades o
                  WHERE o.cliente_id = c.id AND o.deleted_at IS NULL
                    AND o.etapa NOT IN ('ganada','perdida'))                   AS oportunidades_abiertas
            FROM clientes c
            WHERE c.deleted_at IS NULL
            SQL);

        // El embudo desglosado por responsable, para el tablero de Ventas y el
        // reporte de desempeno del equipo. CRM es dueno del embudo en si.
        EsquemaErp::crearVista('v_embudo_por_responsable', <<<'SQL'
            SELECT
                o.asignado_a AS responsable_id,
                u.name       AS responsable_nombre,
                o.etapa,
                COUNT(*)                                           AS oportunidades,
                COALESCE(SUM(o.monto), 0)                          AS monto_total,
                COALESCE(SUM(o.monto * o.probabilidad / 100), 0)   AS monto_ponderado,
                COALESCE(SUM(CASE WHEN o.etapa = 'ganada'  THEN o.monto ELSE 0 END), 0) AS monto_ganado,
                COALESCE(SUM(CASE WHEN o.etapa = 'perdida' THEN o.monto ELSE 0 END), 0) AS monto_perdido
            FROM oportunidades o
            LEFT JOIN users u ON u.id = o.asignado_a
            WHERE o.deleted_at IS NULL
            GROUP BY o.asignado_a, u.name, o.etapa
            SQL);
    }

    public function down(): void
    {
        EsquemaErp::eliminarVista('v_embudo_por_responsable');
        EsquemaErp::eliminarVista('v_historial_cliente');
    }
};
