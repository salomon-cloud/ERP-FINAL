<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;

/**
 * CRM es dueno del embudo. Ventas reporta sobre el con
 * v_embudo_por_responsable, que son los mismos datos agrupados por vendedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        EsquemaErp::crearVista('v_embudo_ventas', <<<'SQL'
            SELECT
                o.etapa,
                COUNT(*)                                         AS oportunidades,
                COALESCE(SUM(o.monto), 0)                        AS monto_total,
                COALESCE(SUM(o.monto * o.probabilidad / 100), 0) AS monto_ponderado,
                MIN(o.fecha_cierre_estimada)                     AS proximo_cierre
            FROM oportunidades o
            WHERE o.deleted_at IS NULL
            GROUP BY o.etapa
            SQL);
    }

    public function down(): void
    {
        EsquemaErp::eliminarVista('v_embudo_ventas');
    }
};
