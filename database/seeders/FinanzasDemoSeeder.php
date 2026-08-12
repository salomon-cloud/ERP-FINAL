<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\RH\Models\Nomina;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Datos de DEMOSTRACION de Finanzas: catalogo de cuentas, periodo fiscal,
 * centros de costo, bancos, una poliza de nomina y un presupuesto.
 *
 * Se escribe con el query builder y no con modelos porque el modulo Finanzas
 * todavia no publica los suyos (app/Modules/Finanzas/Models esta vacio).
 *
 * Corre DESPUES de RhDemoSeeder a proposito: la poliza de nomina y el
 * movimiento bancario del pago se calculan a partir de la corrida que ese
 * seeder dejo aplicada, en vez de traer cifras inventadas que no cuadran con
 * ningun recibo. Si la corrida no existe, esas dos partes se omiten y se avisa.
 *
 * NO debe correr en produccion.
 */
class FinanzasDemoSeeder extends Seeder
{
    /** La corrida de nomina que RhDemoSeeder deja aplicada. */
    private const CORRIDA_DEMO = 'NOM-2026-05-01';

    public function run(): void
    {
        $this->call(UsuariosDemoSeeder::class);

        $contador = UsuariosDemoSeeder::mapa()[UsuariosDemoSeeder::FINANZAS];

        $cuentas = $this->sembrarCatalogoCuentas($contador->id);
        $periodoId = $this->sembrarPeriodoFiscal($contador->id);
        $centros = $this->sembrarCentrosCosto($contador->id);
        $this->sembrarImpuestos($contador->id);
        $this->sembrarTiposCambio();

        $corrida = $this->corridaDeNomina();

        $cuentasBancarias = $this->sembrarCuentasBancarias($contador->id);
        $movimientos = $this->sembrarMovimientosBancarios($contador->id, $cuentasBancarias, $corrida);
        $conciliacion = $this->sembrarConciliacionBancaria($contador->id, $cuentasBancarias);
        $this->sembrarConciliacionLinea($contador->id, $conciliacion, $movimientos);
        $this->sembrarPolizaNomina($contador->id, $periodoId, $cuentas, $corrida);
        $this->sembrarPresupuestos($contador->id, $periodoId, $centros, $cuentas, $corrida);
        $this->sembrarFacturaElectronica($contador->id, $corrida);

        $this->command?->info('Datos demo de Finanzas sembrados.');
    }

    /**
     * La corrida aplicada de RH, con sus totales ya cuadrados contra los
     * recibos. Es la fuente de todas las cifras de nomina de este seeder.
     */
    private function corridaDeNomina(): ?object
    {
        $corrida = DB::table('nomina_corridas')
            ->where('numero_corrida', self::CORRIDA_DEMO)
            ->first();

        if ($corrida === null) {
            $this->command?->warn(
                'No existe la corrida '.self::CORRIDA_DEMO.': se omiten la poliza de nomina y el pago bancario. '
                .'Corre RhDemoSeeder antes que este.'
            );
        }

        return $corrida;
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

    /**
     * Solo hay un periodo fiscal, el del ejercicio en curso, asi que devuelve
     * su id y no un mapa: el mapa anterior tenia la llave '2026' fija mientras
     * el ejercicio salia de now()->year, y quien lo usaba escribia
     * `$periodos['2026']->id` -- una propiedad sobre un int, que es justo donde
     * el seeder reventaba.
     */
    private function sembrarPeriodoFiscal(int $usuarioId): int
    {
        $ejercicio = now()->year;

        $datos = [
            'nombre' => 'Ejercicio '.$ejercicio,
            'ejercicio' => $ejercicio,
            'fecha_inicio' => $ejercicio.'-01-01',
            'fecha_fin' => $ejercicio.'-12-31',
            'estado' => 'abierto',
            'es_periodo_cierre' => false,
            'cerrado_por' => null,
            'cerrado_en' => null,
        ];

        return $this->guardarFila(
            'periodos_fiscales',
            ['ejercicio' => $datos['ejercicio'], 'nombre' => $datos['nombre']],
            $datos + $this->auditoria($usuarioId),
        );
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
    private function sembrarMovimientosBancarios(int $usuarioId, array $cuentasBancarias, ?object $corrida): array
    {
        $movimientos = [
            'DEP' => ['cuenta_bancaria_id' => $cuentasBancarias['OPER'], 'fecha' => now()->subDays(3)->toDateString(), 'concepto' => 'Deposito por cobranza demo', 'monto' => '180000.00', 'estado' => 'conciliado', 'referencia' => 'DEP-DEMO-001'],
        ];

        // El cargo del pago de nomina es EXACTAMENTE el neto de la corrida. Si
        // no coincide, la conciliacion bancaria de la demo nace descuadrada.
        if ($corrida !== null) {
            $movimientos['NOM'] = [
                'cuenta_bancaria_id' => $cuentasBancarias['NOM'],
                'fecha' => now()->subDays(2)->toDateString(),
                'concepto' => 'Pago de nomina '.$corrida->numero_corrida,
                'monto' => '-'.$corrida->total_neto,
                'estado' => 'pendiente',
                'referencia' => $corrida->numero_corrida,
            ];
        }

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

    /**
     * La poliza del pago de nomina, cuadrada contra la corrida real:
     *
     *     Debe  5100 Sueldos y salarios     = percepciones
     *     Haber 2100 Impuestos por pagar    = deducciones (ISR, IMSS y demas)
     *     Haber 1100 Caja y bancos          = neto pagado
     *
     * Antes la poliza declaraba 143,500.00 de total mientras sus dos renglones
     * sumaban 130,000.00: ni cuadraba consigo misma ni con la nomina. Ahora las
     * tres cifras salen de nomina_corridas y el asiento cierra por definicion
     * (percepciones = deducciones + neto).
     */
    private function sembrarPolizaNomina(int $usuarioId, int $periodoId, array $cuentas, ?object $corrida): ?int
    {
        if ($corrida === null) {
            return null;
        }

        $numero = 'POL-'.now()->year.'-0001';

        $polizaId = $this->guardarFila('polizas', ['numero_poliza' => $numero], [
            'numero_poliza' => $numero,
            'periodo_fiscal_id' => $periodoId,
            'fecha' => now()->subDays(2)->toDateString(),
            'concepto' => 'Registro de nomina '.$corrida->numero_corrida,
            'referencia' => $corrida->numero_corrida,
            'origen_tipo' => 'nomina',
            'origen_id' => $corrida->id,
            'estado' => 'contabilizada',
            'total_debe' => $corrida->total_percepciones,
            'total_haber' => $corrida->total_percepciones,
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

        $renglones = [
            ['cuenta' => '5100', 'concepto' => 'Sueldos y salarios del periodo', 'debe' => $corrida->total_percepciones, 'haber' => '0.00'],
            ['cuenta' => '2100', 'concepto' => 'Retenciones y deducciones por pagar', 'debe' => '0.00', 'haber' => $corrida->total_deducciones],
            ['cuenta' => '1100', 'concepto' => 'Pago neto de nomina', 'debe' => '0.00', 'haber' => $corrida->total_neto],
        ];

        foreach ($renglones as $renglon) {
            // chk_poliza_lineas_un_lado exige que un renglon tenga debe O haber,
            // nunca los dos en cero: una corrida sin deducciones no lleva ese
            // renglon en vez de mandarlo en ceros y que la base lo rechace.
            if ((float) $renglon['debe'] <= 0 && (float) $renglon['haber'] <= 0) {
                continue;
            }

            $this->guardarFila(
                'poliza_lineas',
                ['poliza_id' => $polizaId, 'cuenta_id' => $cuentas[$renglon['cuenta']], 'periodo_fiscal_id' => $periodoId],
                [
                    'poliza_id' => $polizaId,
                    'cuenta_id' => $cuentas[$renglon['cuenta']],
                    'periodo_fiscal_id' => $periodoId,
                    'centro_costo_id' => null,
                    'concepto' => $renglon['concepto'],
                    'debe' => $renglon['debe'],
                    'haber' => $renglon['haber'],
                    'referencia' => $corrida->numero_corrida,
                    'created_at' => now(),
                ]
            );
        }

        // La corrida queda apuntando a su poliza, que es justo el enlace que
        // ServicioCorridaNomina::aplicar() dejara de tarea cuando Finanzas
        // publique su contabilizador.
        DB::table('nomina_corridas')->where('id', $corrida->id)->update(['poliza_id' => $polizaId]);

        return $polizaId;
    }

    private function sembrarPresupuestos(int $usuarioId, int $periodoId, array $centros, array $cuentas, ?object $corrida): void
    {
        $nombre = 'Presupuesto anual '.now()->year;

        $presupuestoId = $this->guardarFila('presupuestos', ['periodo_fiscal_id' => $periodoId, 'nombre' => $nombre], [
            'periodo_fiscal_id' => $periodoId,
            'centro_costo_id' => $centros['CORP'],
            'nombre' => $nombre,
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
            // El ejercido de sueldos es lo que de verdad costo la corrida, para
            // que el comparativo presupuesto contra real no mienta.
            ['cuenta_id' => $cuentas['5100'], 'mes' => 5, 'monto_proyectado' => '210000.00', 'monto_real' => $corrida?->total_percepciones ?? '0.00'],
            ['cuenta_id' => $cuentas['4100'], 'mes' => 5, 'monto_proyectado' => '300000.00', 'monto_real' => '320000.00'],
        ];

        foreach ($lineas as $linea) {
            $this->guardarFila('presupuesto_lineas', ['presupuesto_id' => $presupuestoId, 'cuenta_id' => $linea['cuenta_id'], 'mes' => $linea['mes']], $linea + ['presupuesto_id' => $presupuestoId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function sembrarFacturaElectronica(int $usuarioId, ?object $corrida): void
    {
        // Un recibo PAGADO de la corrida demo, no el primero que aparezca en la
        // tabla: un CFDI de nomina timbra un pago que ya ocurrio.
        $nominaId = $corrida === null ? null : DB::table('nominas')
            ->where('corrida_id', $corrida->id)
            ->where('estado', 'pagada')
            ->orderBy('id')
            ->value('id');

        // El unico de la tabla es (serie, folio); `tipo_documento` no forma
        // parte de el y buscarlo de mas dejaria insertar un segundo NOM-0001.
        $this->guardarFila('facturas_electronicas', ['serie' => 'NOM', 'folio' => '0001'], [
            'organizacion_id' => null,
            'serie' => 'NOM',
            'folio' => '0001',
            'tipo_documento' => 'nomina',
            'modelo_tipo' => $nominaId === null ? null : Nomina::class,
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

    /**
     * Alta o actualizacion de una fila, devolviendo su id.
     *
     * En la actualizacion se respeta `created_at`: es la fecha del alta
     * original y volver a sembrar no tiene por que reescribirla. Antes se
     * mandaba siempre, asi que cada corrida del seeder movia la fecha de alta
     * de todo lo que ya existia.
     */
    private function guardarFila(string $tabla, array $unicos, array $datos): int
    {
        $consulta = DB::table($tabla);

        foreach ($unicos as $columna => $valor) {
            $consulta->where($columna, $valor);
        }

        $id = $consulta->value('id');

        if ($id !== null) {
            DB::table($tabla)->where('id', $id)->update(Arr::except($datos, ['created_at']));

            return (int) $id;
        }

        return (int) DB::table($tabla)->insertGetId($unicos + $datos);
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
