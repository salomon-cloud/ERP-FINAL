<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FinanzasDemoSeeder extends Seeder
{
    private const PASSWORD_DEMO = 'password';
    private const MONTO_NOMINA_DEMO = '130000.00';

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'finanzas@sisen.com'],
            [
                'name' => 'Finanzas Demo',
                'role' => 'Contador',
                'password' => Hash::make(self::PASSWORD_DEMO),
                'estado' => 'activo',
                'debe_cambiar_password' => false,
            ]
        );

        $cuentas = $this->sembrarCatalogoCuentas($admin->id);
        $periodos = $this->sembrarPeriodosFiscales($admin->id);
        $centros = $this->sembrarCentrosCosto($admin->id);
        $this->sembrarImpuestos($admin->id);
        $this->sembrarTiposCambio();
        $cuentasBancarias = $this->sembrarCuentasBancarias($admin->id);
        $movimientos = $this->sembrarMovimientosBancarios($admin->id, $cuentasBancarias);
        $conciliacion = $this->sembrarConciliacionBancaria($admin->id, $cuentasBancarias);
        $this->sembrarConciliacionLinea($admin->id, $conciliacion, $movimientos);
        $this->sembrarPolizaNomina($admin->id, $periodos, $cuentas);
        $this->sembrarPresupuestos($admin->id, $periodos, $centros, $cuentas);
        $this->sembrarFacturaElectronica($admin->id);

        $this->command?->info('Datos demo de Finanzas sembrados.');
    }

    /** @return array<string, int> */
    private function sembrarCatalogoCuentas(int $usuarioId): array
    {
        $cuentas = [
            '1000' => ['codigo' => '1000', 'nombre' => 'Activo circulante', 'tipo_cuenta' => 'activo', 'naturaleza' => 'deudora', 'es_encabezado' => true, 'permite_movimientos' => false, 'es_efectivo' => false, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
            '1100' => ['codigo' => '1100', 'nombre' => 'Caja y bancos', 'tipo_cuenta' => 'activo', 'naturaleza' => 'deudora', 'es_encabezado' => false, 'permite_movimientos' => true, 'es_efectivo' => true, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
            '4100' => ['codigo' => '4100', 'nombre' => 'Ingresos por ventas', 'tipo_cuenta' => 'ingreso', 'naturaleza' => 'acreedora', 'es_encabezado' => false, 'permite_movimientos' => true, 'es_efectivo' => false, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
            '5100' => ['codigo' => '5100', 'nombre' => 'Sueldos y salarios', 'tipo_cuenta' => 'egreso', 'naturaleza' => 'deudora', 'es_encabezado' => false, 'permite_movimientos' => true, 'es_efectivo' => false, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
            '2100' => ['codigo' => '2100', 'nombre' => 'Impuestos por pagar', 'tipo_cuenta' => 'pasivo', 'naturaleza' => 'acreedora', 'es_encabezado' => false, 'permite_movimientos' => true, 'es_efectivo' => false, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
            '3000' => ['codigo' => '3000', 'nombre' => 'Capital contable', 'tipo_cuenta' => 'capital', 'naturaleza' => 'acreedora', 'es_encabezado' => true, 'permite_movimientos' => false, 'es_efectivo' => false, 'moneda' => 'MXN', 'activo' => true, 'padre_id' => null],
        ];

        $ids = [];

        foreach ($cuentas as $codigo => $datos) {
            $ids[$codigo] = $this->guardarFila('catalogo_cuentas', ['codigo' => $datos['codigo']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function sembrarPeriodosFiscales(int $usuarioId): array
    {
        $ejercicio = now()->year;
        $periodos = [
            '2026' => [
                'nombre' => 'Ejercicio '.$ejercicio,
                'ejercicio' => $ejercicio,
                'fecha_inicio' => sprintf('%d-01-01', $ejercicio),
                'fecha_fin' => sprintf('%d-12-31', $ejercicio),
                'estado' => 'abierto',
                'es_periodo_cierre' => false,
                'cerrado_por' => null,
                'cerrado_en' => null,
            ],
        ];

        $ids = [];

        foreach ($periodos as $clave => $datos) {
            $ids[$clave] = $this->guardarFila('periodos_fiscales', ['ejercicio' => $datos['ejercicio'], 'nombre' => $datos['nombre']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function sembrarCentrosCosto(int $usuarioId): array
    {
        $centros = [
            'CORP' => ['codigo' => 'CORP', 'nombre' => 'Corporativo', 'descripcion' => 'Gastos compartidos de la empresa', 'padre_id' => null, 'activo' => true],
            'RH' => ['codigo' => 'RH', 'nombre' => 'Recursos Humanos', 'descripcion' => 'Nómina y gestión de personal', 'padre_id' => null, 'activo' => true],
            'VENTAS' => ['codigo' => 'VENTAS', 'nombre' => 'Ventas', 'descripcion' => 'Comercial y cobranza', 'padre_id' => null, 'activo' => true],
            'OPS' => ['codigo' => 'OPS', 'nombre' => 'Operaciones', 'descripcion' => 'Operación y logística', 'padre_id' => null, 'activo' => true],
        ];

        $ids = [];

        foreach ($centros as $clave => $datos) {
            $ids[$clave] = $this->guardarFila('centros_costo', ['codigo' => $datos['codigo']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function sembrarImpuestos(int $usuarioId): array
    {
        $impuestos = [
            'IVA16' => ['codigo' => 'IVA16', 'nombre' => 'IVA 16%', 'tasa' => '0.160000', 'tipo' => 'trasladado', 'activo' => true],
            'ISR10' => ['codigo' => 'ISR10', 'nombre' => 'ISR 10%', 'tasa' => '0.100000', 'tipo' => 'retenido', 'activo' => true],
        ];

        $ids = [];

        foreach ($impuestos as $clave => $datos) {
            $ids[$clave] = $this->guardarFila('impuestos', ['codigo' => $datos['codigo']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    private function sembrarTiposCambio(): void
    {
        $this->guardarFila('tipos_cambio', ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => now()->toDateString()], [
            'moneda_origen' => 'USD',
            'moneda_destino' => 'MXN',
            'fecha' => now()->toDateString(),
            'tasa' => '17.250000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guardarFila('tipos_cambio', ['moneda_origen' => 'EUR', 'moneda_destino' => 'MXN', 'fecha' => now()->toDateString()], [
            'moneda_origen' => 'EUR',
            'moneda_destino' => 'MXN',
            'fecha' => now()->toDateString(),
            'tasa' => '18.900000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, int> */
    private function sembrarCuentasBancarias(int $usuarioId): array
    {
        $cuentas = [
            'OPER' => ['nombre' => 'Cuenta operativa', 'banco' => 'Banco Nacional', 'numero_cuenta' => '1234567890', 'tipo_cuenta' => 'cheques', 'moneda' => 'MXN', 'saldo_inicial' => '50000.00', 'activo' => true],
            'NOM' => ['nombre' => 'Cuenta de nómina', 'banco' => 'Banco Nacional', 'numero_cuenta' => '9876543210', 'tipo_cuenta' => 'ahorro', 'moneda' => 'MXN', 'saldo_inicial' => '120000.00', 'activo' => true],
        ];

        $ids = [];

        foreach ($cuentas as $clave => $datos) {
            $ids[$clave] = $this->guardarFila('cuentas_bancarias', ['numero_cuenta' => $datos['numero_cuenta']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function sembrarMovimientosBancarios(int $usuarioId, array $cuentasBancarias): array
    {
        $movimientos = [
            'DEP' => ['cuenta_bancaria_id' => $cuentasBancarias['OPER'], 'fecha' => now()->subDays(3)->toDateString(), 'concepto' => 'Depósito por cobranza demo', 'monto' => '180000.00', 'estado' => 'conciliado', 'referencia' => 'DEP-DEMO-001'],
            'NOM' => ['cuenta_bancaria_id' => $cuentasBancarias['NOM'], 'fecha' => now()->subDays(2)->toDateString(), 'concepto' => 'Pago de nómina demo', 'monto' => '-130000.00', 'estado' => 'pendiente', 'referencia' => 'NOM-DEMO-001'],
        ];

        $ids = [];

        foreach ($movimientos as $clave => $datos) {
            $ids[$clave] = $this->guardarFila('movimientos_bancarios', ['cuenta_bancaria_id' => $datos['cuenta_bancaria_id'], 'fecha' => $datos['fecha'], 'referencia' => $datos['referencia']], $datos + $this->auditoria($usuarioId));
        }

        return $ids;
    }

    /** @return int */
    private function sembrarConciliacionBancaria(int $usuarioId, array $cuentasBancarias): int
    {
        return $this->guardarFila('conciliaciones_bancarias', ['cuenta_bancaria_id' => $cuentasBancarias['OPER'], 'fecha_inicio' => now()->subWeek()->toDateString(), 'fecha_fin' => now()->toDateString()], [
            'cuenta_bancaria_id' => $cuentasBancarias['OPER'],
            'fecha_inicio' => now()->subWeek()->toDateString(),
            'fecha_fin' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => '50000.00',
            'saldo_final' => '230000.00',
            'conciliada_por' => null,
            'conciliada_en' => null,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sembrarConciliacionLinea(int $usuarioId, int $conciliacionId, array $movimientos): void
    {
        $this->guardarFila('conciliacion_lineas', ['conciliacion_id' => $conciliacionId, 'movimiento_bancario_id' => $movimientos['DEP']], [
            'conciliacion_id' => $conciliacionId,
            'movimiento_bancario_id' => $movimientos['DEP'],
            'poliza_id' => null,
            'conciliada_por' => $usuarioId,
            'conciliada_en' => now(),
        ]);
    }

    private function sembrarPolizaNomina(int $usuarioId, array $periodos, array $cuentas): int
    {
        $polizaId = $this->guardarFila('polizas', ['numero_poliza' => 'POL-2026-0001'], [
            'numero_poliza' => 'POL-2026-0001',
            'periodo_fiscal_id' => $periodos['2026']->id,
            'fecha' => now()->subDays(2)->toDateString(),
            'concepto' => 'Registro de nómina demo',
            'referencia' => 'NOM-DEMO-001',
            'origen_tipo' => 'nomina',
            'origen_id' => 1,
            'estado' => 'contabilizada',
            'total_debe' => '143500.00',
            'total_haber' => '143500.00',
            'contabilizada_por' => $usuarioId,
            'contabilizada_en' => now(),
            'cancelada_por' => null,
            'motivo_cancelacion' => null,
            'version_fila' => 1,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guardarFila('poliza_lineas', ['poliza_id' => $polizaId, 'cuenta_id' => $cuentas['5100'], 'periodo_fiscal_id' => $periodos['2026']->id], [
            'poliza_id' => $polizaId,
            'cuenta_id' => $cuentas['5100'],
            'periodo_fiscal_id' => $periodos['2026']->id,
            'centro_costo_id' => null,
            'concepto' => 'Sueldos y salarios demo',
            'debe' => self::MONTO_NOMINA_DEMO,
            'haber' => '0.00',
            'referencia' => 'NOM-DEMO-001',
            'created_at' => now(),
        ]);

        $this->guardarFila('poliza_lineas', ['poliza_id' => $polizaId, 'cuenta_id' => $cuentas['2100'], 'periodo_fiscal_id' => $periodos['2026']->id], [
            'poliza_id' => $polizaId,
            'cuenta_id' => $cuentas['2100'],
            'periodo_fiscal_id' => $periodos['2026']->id,
            'centro_costo_id' => null,
            'concepto' => 'Pasivo de nómina demo',
            'debe' => '0.00',
            'haber' => self::MONTO_NOMINA_DEMO,
            'referencia' => 'NOM-DEMO-001',
            'created_at' => now(),
        ]);

        return $polizaId;
    }

    private function sembrarPresupuestos(int $usuarioId, array $periodos, array $centros, array $cuentas): void
    {
        $presupuestoId = $this->guardarFila('presupuestos', ['periodo_fiscal_id' => $periodos['2026']->id, 'nombre' => 'Presupuesto anual 2026'], [
            'periodo_fiscal_id' => $periodos['2026']->id,
            'centro_costo_id' => $centros['CORP'],
            'nombre' => 'Presupuesto anual 2026',
            'estado' => 'aprobado',
            'monto_total' => '2500000.00',
            'aprobado_por' => $usuarioId,
            'aprobado_en' => now(),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lineas = [
            ['cuenta_id' => $cuentas['5100'], 'mes' => 1, 'monto_proyectado' => '210000.00', 'monto_real' => '130000.00'],
            ['cuenta_id' => $cuentas['4100'], 'mes' => 1, 'monto_proyectado' => '300000.00', 'monto_real' => '320000.00'],
        ];

        foreach ($lineas as $linea) {
            $this->guardarFila('presupuesto_lineas', ['presupuesto_id' => $presupuestoId, 'cuenta_id' => $linea['cuenta_id'], 'mes' => $linea['mes']], $linea + ['presupuesto_id' => $presupuestoId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function sembrarFacturaElectronica(int $usuarioId): void
    {
        $nominaId = DB::table('nominas')->value('id');

        $this->guardarFila('facturas_electronicas', ['serie' => 'NOM', 'folio' => '0001', 'tipo_documento' => 'nomina'], [
            'organizacion_id' => null,
            'serie' => 'NOM',
            'folio' => '0001',
            'tipo_documento' => 'nomina',
            'modelo_tipo' => $nominaId ? 'App\\Modules\\RH\\Models\\Nomina' : null,
            'modelo_id' => $nominaId,
            'uuid' => null,
            'ruta_xml' => null,
            'ruta_pdf' => null,
            'estado' => 'generada',
            'respuesta_timbrado' => null,
            'cancelada_en' => null,
            'uuid_cancelacion' => null,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function guardarFila(string $tabla, array $unicos, array $datos): int
    {
        DB::table($tabla)->updateOrInsert($unicos, $datos);

        $consulta = DB::table($tabla);

        foreach ($unicos as $columna => $valor) {
            $consulta->where($columna, $valor);
        }

        return (int) $consulta->value('id');
    }

    private function auditoria(int $usuarioId): array
    {
        return [
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
