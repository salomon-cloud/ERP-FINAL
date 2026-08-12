<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\EstadoContrato;
use App\Modules\RH\Enums\EstadoDocumentoEmpleado;
use App\Modules\RH\Enums\EstadoEvaluacionDesempeno;
use App\Modules\RH\Enums\EstadoNominaCorrida;
use App\Modules\RH\Enums\EstadoNominaPeriodo;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Enums\FrecuenciaPago;
use App\Modules\RH\Enums\GeneroEmpleado;
use App\Modules\RH\Enums\TipoContrato;
use App\Modules\RH\Enums\TipoDocumentoEmpleado;
use App\Modules\RH\Enums\TipoPermiso;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Contrato;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\DocumentoEmpleado;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\EvaluacionDesempeno;
use App\Modules\RH\Models\Nomina;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Models\Permiso;
use App\Modules\RH\Models\Puesto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RhDemoSeeder extends Seeder
{
    private const NOMBRE_RRHH = 'Recursos Humanos';
    private const FECHA_CONTRATACION = '2024-01-15';
    private const FECHA_BAJA_DEMO = '2026-05-15';
    private const FECHA_PAGO_DEMO = '2026-05-15';
    private const FECHA_FIRMADO_DEMO = '2024-01-15 09:00:00';
    private const FECHA_APLICADA_DEMO = '2026-05-15 12:00:00';
    private const FECHA_ASISTENCIA_1 = '2026-05-25';
    private const FECHA_ASISTENCIA_2 = '2026-05-26';
    private const HORA_ENTRADA_DEMO = '08:00';
    private const HORA_SALIDA_DEMO = '17:00';
    private const NOTA_SEEDING = 'Seeding demo.';
    private const PERIODO_EVALUACION = '2026-S1';

    public function run(): void
    {
        $usuarios = $this->sembrarUsuarios();
        $departamentos = $this->sembrarDepartamentos($usuarios);
        $puestos = $this->sembrarPuestos($departamentos);
        $empleados = $this->sembrarEmpleados($departamentos, $puestos, $usuarios);
        $periodos = $this->sembrarPeriodosNomina();
        $corridas = $this->sembrarCorridas($periodos, $usuarios);

        $this->sembrarContratos($empleados);
        $this->sembrarNominas($empleados, $corridas);
        $this->sembrarAsistencias($empleados, $usuarios);
        $this->sembrarPermisos($empleados, $usuarios);
        $this->sembrarDocumentos($empleados);
        $this->sembrarEvaluaciones($empleados);

        $this->command?->info('Datos demo de RH sembrados.');
    }

    /** @return array<string, User> */
    private function sembrarUsuarios(): array
    {
        $usuarios = [
            'admin@sisen.com' => ['name' => 'Administrador SISEN', 'role' => 'Administrador'],
            'rh@sisen.com' => ['name' => self::NOMBRE_RRHH, 'role' => self::NOMBRE_RRHH],
            'contador@sisen.com' => ['name' => 'Contador', 'role' => 'Contador'],
            'empleado@sisen.com' => ['name' => 'Empleado Demo', 'role' => 'Empleado'],
        ];

        $mapa = [];

        foreach ($usuarios as $email => $datos) {
            $mapa[$email] = User::updateOrCreate(
                ['email' => $email],
                $datos + [
                    'password' => Hash::make('password'),
                    'estado' => 'activo',
                    'debe_cambiar_password' => false,
                ]
            );
        }

        return $mapa;
    }

    /** @return array<string, Departamento> */
    private function sembrarDepartamentos(array $usuarios): array
    {
        $definidos = [
            'RH' => ['nombre' => self::NOMBRE_RRHH, 'descripcion' => 'Gestion de talento, contratos y clima laboral.', 'responsable' => 'Laura Martinez', 'codigo' => 'RH'],
            'FIN' => ['nombre' => 'Finanzas', 'descripcion' => 'Contabilidad, pagos y reportes financieros.', 'responsable' => 'Carlos Rivera', 'codigo' => 'FIN'],
            'OPS' => ['nombre' => 'Operaciones', 'descripcion' => 'Procesos internos y productividad.', 'responsable' => 'Miriam Torres', 'codigo' => 'OPS'],
            'TEC' => ['nombre' => 'Tecnologia', 'descripcion' => 'Soporte, sistemas y automatizacion.', 'responsable' => 'Andres Gomez', 'codigo' => 'TEC'],
        ];

        $mapa = [];

        foreach ($definidos as $clave => $datos) {
            $mapa[$clave] = Departamento::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'codigo' => $datos['codigo'],
                    'descripcion' => $datos['descripcion'],
                    'responsable' => $datos['responsable'],
                    'estado' => EstadoActivacion::Activo->value,
                    'organizacion_id' => null,
                    'padre_id' => null,
                    'jefe_id' => null,
                    'creado_por' => $usuarios['admin@sisen.com']->id,
                    'actualizado_por' => $usuarios['admin@sisen.com']->id,
                ]
            );
        }

        return $mapa;
    }

    /** @return array<string, Puesto> */
    private function sembrarPuestos(array $departamentos): array
    {
        $puestos = [
            'RH' => ['departamento_id' => $departamentos['RH']->id, 'nombre' => 'Coordinador RH', 'descripcion' => 'Administracion de personal.', 'codigo' => 'RH-COOR', 'sueldo_minimo' => 18000, 'sueldo_maximo' => 28000],
            'FIN' => ['departamento_id' => $departamentos['FIN']->id, 'nombre' => 'Contador General', 'descripcion' => 'Nominas e impuestos.', 'codigo' => 'FIN-CONT', 'sueldo_minimo' => 22000, 'sueldo_maximo' => 36000],
            'OPS' => ['departamento_id' => $departamentos['OPS']->id, 'nombre' => 'Supervisor Operativo', 'descripcion' => 'Seguimiento de equipos.', 'codigo' => 'OPS-SUP', 'sueldo_minimo' => 17000, 'sueldo_maximo' => 30000],
            'TEC' => ['departamento_id' => $departamentos['TEC']->id, 'nombre' => 'Analista de Sistemas', 'descripcion' => 'Soporte a plataformas internas.', 'codigo' => 'TEC-ANL', 'sueldo_minimo' => 24000, 'sueldo_maximo' => 42000],
        ];

        $mapa = [];

        foreach ($puestos as $clave => $datos) {
            $mapa[$clave] = Puesto::updateOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + [
                    'estado' => EstadoActivacion::Activo->value,
                    'organizacion_id' => null,
                    'creado_por' => null,
                    'actualizado_por' => null,
                ]
            );
        }

        return $mapa;
    }

    /** @return array<string, Empleado> */
    private function sembrarEmpleados(array $departamentos, array $puestos, array $usuarios): array
    {
        $empleados = [
            'LAU' => [
                'numero_empleado' => 'EMP-001',
                'nombre' => 'Laura',
                'apellidos' => 'Martinez Lopez',
                'genero' => GeneroEmpleado::Femenino,
                'curp' => 'MALL900101MDFRPR01',
                'rfc' => 'MALL900101AB1',
                'correo' => 'laura@sisen.com',
                'telefono' => '5551001001',
                'departamento_id' => $departamentos['RH']->id,
                'puesto_id' => $puestos['RH']->id,
                'user_id' => $usuarios['rh@sisen.com']->id,
                'sueldo_base' => 26000,
            ],
            'CAR' => [
                'numero_empleado' => 'EMP-002',
                'nombre' => 'Carlos',
                'apellidos' => 'Rivera Santos',
                'genero' => GeneroEmpleado::Masculino,
                'curp' => 'RISC880202HDFRNR02',
                'rfc' => 'RISC880202CD2',
                'correo' => 'carlos@sisen.com',
                'telefono' => '5551001002',
                'departamento_id' => $departamentos['FIN']->id,
                'puesto_id' => $puestos['FIN']->id,
                'user_id' => $usuarios['contador@sisen.com']->id,
                'sueldo_base' => 32000,
            ],
            'MIR' => [
                'numero_empleado' => 'EMP-003',
                'nombre' => 'Miriam',
                'apellidos' => 'Torres Vega',
                'genero' => GeneroEmpleado::Femenino,
                'curp' => 'TOVM920303MDFRGR03',
                'rfc' => 'TOVM920303EF3',
                'correo' => 'miriam@sisen.com',
                'telefono' => '5551001003',
                'departamento_id' => $departamentos['OPS']->id,
                'puesto_id' => $puestos['OPS']->id,
                'user_id' => null,
                'sueldo_base' => 24500,
            ],
            'AND' => [
                'numero_empleado' => 'EMP-004',
                'nombre' => 'Andres',
                'apellidos' => 'Gomez Diaz',
                'genero' => GeneroEmpleado::Masculino,
                'curp' => 'GODA940404HDFMRN04',
                'rfc' => 'GODA940404GH4',
                'correo' => 'andres@sisen.com',
                'telefono' => '5551001004',
                'departamento_id' => $departamentos['TEC']->id,
                'puesto_id' => $puestos['TEC']->id,
                'user_id' => $usuarios['empleado@sisen.com']->id,
                'sueldo_base' => 38000,
            ],
        ];

        $mapa = [];

        foreach ($empleados as $clave => $datos) {
            $mapa[$clave] = Empleado::updateOrCreate(
                ['curp' => $datos['curp']],
                [
                    'organizacion_id' => null,
                    'numero_empleado' => $datos['numero_empleado'],
                    'departamento_id' => $datos['departamento_id'],
                    'puesto_id' => $datos['puesto_id'],
                    'jefe_id' => null,
                    'user_id' => $datos['user_id'],
                    'nombre' => $datos['nombre'],
                    'apellidos' => $datos['apellidos'],
                    'genero' => $datos['genero']->value,
                    'rfc' => $datos['rfc'],
                    'nss' => null,
                    'banco' => 'Bancomer',
                    'cuenta_bancaria' => '000000000'.$datos['numero_empleado'],
                    'correo' => $datos['correo'],
                    'telefono' => $datos['telefono'],
                    'direccion' => 'Av. Empresa 123, Ciudad de Mexico',
                    'fecha_nacimiento' => '1990-01-01',
                    'fecha_contratacion' => self::FECHA_CONTRATACION,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'tipo_contrato' => TipoContrato::Indefinido->value,
                    'sueldo_base' => $datos['sueldo_base'],
                    'moneda' => 'MXN',
                    'frecuencia_pago' => FrecuenciaPago::Quincenal->value,
                    'estado' => EstadoActivacion::Activo->value,
                    'fotografia' => null,
                ]
            );
        }

        User::where('email', 'rh@sisen.com')->update(['empleado_id' => $mapa['LAU']->id]);
        User::where('email', 'contador@sisen.com')->update(['empleado_id' => $mapa['CAR']->id]);
        User::where('email', 'empleado@sisen.com')->update(['empleado_id' => $mapa['AND']->id]);

        return $mapa;
    }

    /** @return array<string, NominaPeriodo> */
    private function sembrarPeriodosNomina(): array
    {
        $periodos = [
            '2026-05' => [
                'codigo_periodo' => '2026-05',
                'fecha_inicio' => '2026-05-01',
                    'fecha_fin' => self::FECHA_BAJA_DEMO,
                'fecha_pago' => self::FECHA_PAGO_DEMO,
                'frecuencia' => FrecuenciaPago::Quincenal->value,
                'estado' => EstadoNominaPeriodo::Procesado->value,
            ],
            '2026-06' => [
                'codigo_periodo' => '2026-06',
                'fecha_inicio' => '2026-06-01',
                'fecha_fin' => '2026-06-15',
                'fecha_pago' => '2026-06-15',
                'frecuencia' => FrecuenciaPago::Quincenal->value,
                'estado' => EstadoNominaPeriodo::Abierto->value,
            ],
        ];

        $mapa = [];

        foreach ($periodos as $clave => $datos) {
            $mapa[$clave] = NominaPeriodo::updateOrCreate(
                ['codigo_periodo' => $datos['codigo_periodo']],
                $datos + ['organizacion_id' => null]
            );
        }

        return $mapa;
    }

    /** @return array<string, NominaCorrida> */
    private function sembrarCorridas(array $periodos, array $usuarios): array
    {
        $corridas = [
            '2026-05-A' => [
                'periodo_id' => $periodos['2026-05']->id,
                'numero_corrida' => 'NOM-2026-05-01',
                'estado' => EstadoNominaCorrida::Aplicada->value,
                'total_empleados' => 4,
                'total_percepciones' => 143500,
                'total_deducciones' => 13500,
                'total_neto' => 130000,
                'procesada_por' => $usuarios['rh@sisen.com']->id,
                'aprobada_por' => $usuarios['admin@sisen.com']->id,
                'generada_en' => '2026-05-14 10:00:00',
                'aplicada_en' => self::FECHA_APLICADA_DEMO,
            ],
            '2026-06-B' => [
                'periodo_id' => $periodos['2026-06']->id,
                'numero_corrida' => 'NOM-2026-06-01',
                'estado' => EstadoNominaCorrida::Borrador->value,
                'total_empleados' => 0,
                'total_percepciones' => 0,
                'total_deducciones' => 0,
                'total_neto' => 0,
                'procesada_por' => null,
                'aprobada_por' => null,
                'generada_en' => null,
                'aplicada_en' => null,
            ],
        ];

        $mapa = [];

        foreach ($corridas as $clave => $datos) {
            $mapa[$clave] = NominaCorrida::withoutEvents(function () use ($datos) {
                return NominaCorrida::updateOrCreate(
                    ['numero_corrida' => $datos['numero_corrida']],
                    [
                        'organizacion_id' => null,
                        'periodo_id' => $datos['periodo_id'],
                        'estado' => $datos['estado'],
                        'total_empleados' => $datos['total_empleados'],
                        'total_percepciones' => $datos['total_percepciones'],
                        'total_deducciones' => $datos['total_deducciones'],
                        'total_neto' => $datos['total_neto'],
                        'procesada_por' => $datos['procesada_por'],
                        'aprobada_por' => $datos['aprobada_por'],
                        'generada_en' => $datos['generada_en'],
                        'aplicada_en' => $datos['aplicada_en'],
                        'version_fila' => 1,
                    ]
                );
            });
        }

        return $mapa;
    }

    private function sembrarContratos(array $empleados): void
    {
        $contratos = [
            ['empleado' => $empleados['LAU'], 'numero' => 'CON-2024-001', 'tipo' => TipoContrato::Indefinido->value, 'inicio' => self::FECHA_CONTRATACION, 'fin' => null, 'sueldo' => 26000, 'estado' => EstadoContrato::Vigente->value],
            ['empleado' => $empleados['CAR'], 'numero' => 'CON-2024-002', 'tipo' => TipoContrato::Indefinido->value, 'inicio' => self::FECHA_CONTRATACION, 'fin' => null, 'sueldo' => 32000, 'estado' => EstadoContrato::Vigente->value],
            ['empleado' => $empleados['MIR'], 'numero' => 'CON-2024-003', 'tipo' => TipoContrato::Temporal->value, 'inicio' => '2024-02-01', 'fin' => '2026-12-31', 'sueldo' => 24500, 'estado' => EstadoContrato::Vigente->value],
            ['empleado' => $empleados['AND'], 'numero' => 'CON-2024-004', 'tipo' => TipoContrato::Indefinido->value, 'inicio' => '2024-03-01', 'fin' => null, 'sueldo' => 38000, 'estado' => EstadoContrato::Vigente->value],
        ];

        foreach ($contratos as $contrato) {
            Contrato::updateOrCreate(
                ['numero_contrato' => $contrato['numero']],
                [
                    'empleado_id' => $contrato['empleado']->id,
                    'tipo_contrato' => $contrato['tipo'],
                    'fecha_inicio' => $contrato['inicio'],
                    'fecha_fin' => $contrato['fin'],
                    'sueldo' => $contrato['sueldo'],
                    'jornada_horas' => 48,
                    'resumen_clausulas' => 'Contrato demo para operacion del ERP.',
                    'estado' => $contrato['estado'],
                    'firmado_en' => self::FECHA_FIRMADO_DEMO,
                ]
            );
        }
    }

    private function sembrarNominas(array $empleados, array $corridas): void
    {
        $filas = [
            ['empleado' => $empleados['LAU'], 'corrida' => $corridas['2026-05-A'], 'bonos' => 1200, 'horas_extra' => 500, 'deducciones' => 250, 'isr' => 900, 'imss' => 450, 'estado' => 'pagada', 'pagada_en' => '2026-05-16 10:30:00'],
            ['empleado' => $empleados['CAR'], 'corrida' => $corridas['2026-05-A'], 'bonos' => 1450, 'horas_extra' => 500, 'deducciones' => 250, 'isr' => 1000, 'imss' => 450, 'estado' => 'pendiente', 'pagada_en' => null],
            ['empleado' => $empleados['MIR'], 'corrida' => $corridas['2026-05-A'], 'bonos' => 1700, 'horas_extra' => 500, 'deducciones' => 250, 'isr' => 1100, 'imss' => 450, 'estado' => 'pagada', 'pagada_en' => '2026-05-16 10:30:00'],
            ['empleado' => $empleados['AND'], 'corrida' => $corridas['2026-05-A'], 'bonos' => 1950, 'horas_extra' => 500, 'deducciones' => 250, 'isr' => 1200, 'imss' => 450, 'estado' => 'pendiente', 'pagada_en' => null],
        ];

        foreach ($filas as $fila) {
            $datos = [
                'empleado_id' => $fila['empleado']->id,
                'corrida_id' => $fila['corrida']->id,
                'periodo_pago' => 'Primera quincena mayo 2026',
                'fecha_pago' => self::FECHA_PAGO_DEMO,
                'sueldo_base' => (float) $fila['empleado']->sueldo_base / 2,
                'bonos' => $fila['bonos'],
                'horas_extra' => $fila['horas_extra'],
                'horas_extra_cantidad' => 4,
                'deducciones' => $fila['deducciones'],
                'dias_ausencia' => 0,
                'isr' => $fila['isr'],
                'imss' => $fila['imss'],
                'estado' => $fila['estado'],
                'pagada_en' => $fila['pagada_en'],
                'notas' => self::NOTA_SEEDING,
            ];
            $datos['total_pagar'] = Nomina::calcularTotal($datos);

            Nomina::updateOrCreate(
                ['empleado_id' => $fila['empleado']->id, 'periodo_pago' => $datos['periodo_pago']],
                $datos
            );
        }
    }

    private function sembrarAsistencias(array $empleados, array $usuarios): void
    {
        $filas = [
            [$empleados['LAU'], self::FECHA_ASISTENCIA_1, self::HORA_ENTRADA_DEMO, self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['LAU'], self::FECHA_ASISTENCIA_2, '08:18', '17:05', 'retardo'],
            [$empleados['CAR'], self::FECHA_ASISTENCIA_1, '08:02', self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['CAR'], self::FECHA_ASISTENCIA_2, '08:10', self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['MIR'], self::FECHA_ASISTENCIA_1, self::HORA_ENTRADA_DEMO, self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['MIR'], self::FECHA_ASISTENCIA_2, self::HORA_ENTRADA_DEMO, self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['AND'], self::FECHA_ASISTENCIA_1, self::HORA_ENTRADA_DEMO, self::HORA_SALIDA_DEMO, 'presente'],
            [$empleados['AND'], self::FECHA_ASISTENCIA_2, self::HORA_ENTRADA_DEMO, self::HORA_SALIDA_DEMO, 'presente'],
        ];

        foreach ($filas as [$empleado, $fecha, $entrada, $salida, $estado]) {
            Asistencia::updateOrCreate(
                ['empleado_id' => $empleado->id, 'fecha' => $fecha],
                [
                    'hora_entrada' => $entrada,
                    'hora_salida' => $salida,
                    'horas_trabajadas' => 8.00,
                    'estado' => $estado,
                    'notas' => $estado === 'retardo' ? self::NOTA_SEEDING : null,
                    'verificado_por' => $usuarios['rh@sisen.com']->id,
                ]
            );
        }
    }

    private function sembrarPermisos(array $empleados, array $usuarios): void
    {
        $permisos = [
            [$empleados['LAU'], TipoPermiso::Vacaciones->value, '2026-06-03', '2026-06-07', 5, true, 'Periodo vacacional programado.', EstadoPermiso::Pendiente->value],
            [$empleados['MIR'], TipoPermiso::Permiso->value, '2026-05-30', '2026-05-30', 1, false, 'Tramite personal.', EstadoPermiso::Aprobado->value],
        ];

        foreach ($permisos as [$empleado, $tipo, $inicio, $fin, $dias, $goce, $motivo, $estado]) {
            Permiso::updateOrCreate(
                ['empleado_id' => $empleado->id, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin],
                [
                    'tipo' => $tipo,
                    'dias' => $dias,
                    'con_goce' => $goce,
                    'motivo' => $motivo,
                    'estado' => $estado,
                    'revisado_por' => $estado === EstadoPermiso::Aprobado->value ? $usuarios['rh@sisen.com']->id : null,
                    'revisado_en' => $estado === EstadoPermiso::Aprobado->value ? '2026-05-29 12:00:00' : null,
                    'comentario_revision' => $estado === EstadoPermiso::Aprobado->value ? 'Seeding demo.' : null,
                ]
            );
        }
    }

    private function sembrarDocumentos(array $empleados): void
    {
        $documentos = [
            [$empleados['LAU'], TipoDocumentoEmpleado::Contrato->value, 'Contrato laboral', '2027-01-15', EstadoDocumentoEmpleado::Vigente->value],
            [$empleados['CAR'], TipoDocumentoEmpleado::Fiscal->value, 'Constancia fiscal', '2026-12-31', EstadoDocumentoEmpleado::Vigente->value],
            [$empleados['MIR'], TipoDocumentoEmpleado::Identificacion->value, 'Identificacion oficial', null, EstadoDocumentoEmpleado::Pendiente->value],
            [$empleados['AND'], TipoDocumentoEmpleado::Salud->value, 'Examen medico', '2026-09-30', EstadoDocumentoEmpleado::Vigente->value],
        ];

        foreach ($documentos as [$empleado, $tipo, $titulo, $vigencia, $estado]) {
            DocumentoEmpleado::updateOrCreate(
                ['empleado_id' => $empleado->id, 'tipo_documento' => $tipo, 'titulo' => $titulo],
                [
                    'adjunto_id' => null,
                    'vigencia' => $vigencia,
                    'estado' => $estado,
                ]
            );
        }
    }

    private function sembrarEvaluaciones(array $empleados): void
    {
        $evaluaciones = [
            [$empleados['LAU'], $empleados['CAR'], self::PERIODO_EVALUACION, 92.5, 'Liderazgo y orden documental.', 'Alinear mejor tiempos de cierre.', EstadoEvaluacionDesempeno::Reconocida->value],
            [$empleados['CAR'], $empleados['LAU'], self::PERIODO_EVALUACION, 88.0, 'Analisis financiero y control.', 'Mejorar automatizacion de reportes.', EstadoEvaluacionDesempeno::Enviada->value],
            [$empleados['MIR'], $empleados['CAR'], self::PERIODO_EVALUACION, 85.0, 'Cumplimiento operativo.', 'Aumentar trazabilidad.', EstadoEvaluacionDesempeno::Borrador->value],
        ];

        foreach ($evaluaciones as [$empleado, $evaluador, $periodo, $calificacion, $fortalezas, $mejora, $estado]) {
            EvaluacionDesempeno::updateOrCreate(
                ['empleado_id' => $empleado->id, 'periodo_evaluado' => $periodo],
                [
                    'evaluador_id' => $evaluador->id,
                    'calificacion' => $calificacion,
                    'fortalezas' => $fortalezas,
                    'areas_mejora' => $mejora,
                    'objetivos' => [
                        ['objetivo' => 'Cumplir plan del periodo', 'metrica' => 'Entregables', 'meta' => '100%', 'logrado' => true],
                    ],
                    'estado' => $estado,
                    'evaluado_en' => $estado === EstadoEvaluacionDesempeno::Borrador->value ? null : '2026-06-15 10:00:00',
                ]
            );
        }
    }
}
