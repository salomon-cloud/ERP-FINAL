<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;

/**
 * v_existencias: la existencia se DERIVA del libro de movimientos.
 *
 * No hay ninguna columna de existencia que mantener sincronizada, asi que el
 * inventario nunca puede alejarse en silencio de su historia de movimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        EsquemaErp::crearVista('v_existencias', <<<'SQL'
            SELECT
                p.id     AS producto_id,
                p.sku,
                p.nombre AS producto_nombre,
                p.categoria_id,
                a.id     AS almacen_id,
                a.codigo AS almacen_codigo,
                a.nombre AS almacen_nombre,
                m.ubicacion_id,
                u.codigo AS ubicacion_codigo,
                SUM(m.cantidad)                    AS existencia,
                SUM(m.cantidad * m.costo_unitario) AS valor_inventario,
                MAX(m.aplicado_en)                 AS ultimo_movimiento_en
            FROM movimientos_inventario m
            JOIN productos p ON p.id = m.producto_id
            JOIN almacenes a ON a.id = m.almacen_id
            LEFT JOIN ubicaciones u ON u.id = m.ubicacion_id
            WHERE m.estado = 'aplicado'
              AND m.deleted_at IS NULL
            GROUP BY p.id, p.sku, p.nombre, p.categoria_id,
                     a.id, a.codigo, a.nombre, m.ubicacion_id, u.codigo
            SQL);
    }

    public function down(): void
    {
        EsquemaErp::eliminarVista('v_existencias');
    }
};
