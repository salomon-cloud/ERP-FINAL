<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Compartido\Models\SecuenciaDocumento;
use Illuminate\Database\Seeder;

/**
 * Los contadores de folio de cada tipo de documento.
 *
 * Sin su fila aqui, ServicioFolios se niega a numerar el documento: es
 * deliberado, porque un ERP nunca debe inventar un folio sobre la marcha.
 */
class SecuenciaDocumentoSeeder extends Seeder
{
    /** @var array<string, string> modulo => prefijo */
    private const SECUENCIAS = [
        // Finanzas
        'polizas' => 'POL-',
        'facturas_electronicas' => 'CFDI-',
        // Ventas
        'cotizaciones' => 'COT-',
        'pedidos' => 'PED-',
        'facturas' => 'FAC-',
        'notas_credito' => 'NC-',
        'cobros' => 'COB-',
        // Compras
        'requisiciones' => 'REQ-',
        'ordenes_compra' => 'OC-',
        'recepciones' => 'REC-',
        'facturas_proveedor' => 'FP-',
        'devoluciones_compra' => 'DEV-',
        'pagos' => 'PAG-',
        // Inventario
        'traspasos' => 'TRA-',
        'ajustes_inventario' => 'AJU-',
        'conteos_inventario' => 'CON-',
        // RH
        'nomina_corridas' => 'NOM-',
    ];

    public function run(): void
    {
        foreach (self::SECUENCIAS as $modulo => $prefijo) {
            SecuenciaDocumento::updateOrCreate(
                ['modulo' => $modulo, 'prefijo' => $prefijo],
                ['relleno' => (int) config('sisen.documentos.relleno', 6), 'activo' => true]
            );
        }
    }
}
