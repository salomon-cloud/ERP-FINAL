<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Permiso;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $departamentos = collect([
            ['nombre' => 'Recursos Humanos', 'descripcion' => 'Gestion de talento, contratos y clima laboral.', 'responsable' => 'Laura Martinez'],
            ['nombre' => 'Finanzas', 'descripcion' => 'Contabilidad, pagos y reportes financieros.', 'responsable' => 'Carlos Rivera'],
            ['nombre' => 'Operaciones', 'descripcion' => 'Procesos internos y productividad.', 'responsable' => 'Miriam Torres'],
            ['nombre' => 'Tecnologia', 'descripcion' => 'Soporte, sistemas y automatizacion.', 'responsable' => 'Andres Gomez'],
        ])->map(fn ($data) => Departamento::create($data));

        $puestos = collect([
            ['departamento_id' => $departamentos[0]->id, 'nombre' => 'Coordinador RH', 'descripcion' => 'Administracion de personal.', 'sueldo_minimo' => 18000, 'sueldo_maximo' => 28000],
            ['departamento_id' => $departamentos[1]->id, 'nombre' => 'Contador General', 'descripcion' => 'Nominas e impuestos.', 'sueldo_minimo' => 22000, 'sueldo_maximo' => 36000],
            ['departamento_id' => $departamentos[2]->id, 'nombre' => 'Supervisor Operativo', 'descripcion' => 'Seguimiento de equipos.', 'sueldo_minimo' => 17000, 'sueldo_maximo' => 30000],
            ['departamento_id' => $departamentos[3]->id, 'nombre' => 'Analista de Sistemas', 'descripcion' => 'Soporte a plataformas internas.', 'sueldo_minimo' => 24000, 'sueldo_maximo' => 42000],
        ])->map(fn ($data) => Puesto::create($data));

        $empleados = collect([
            ['nombre' => 'Laura', 'apellidos' => 'Martinez Lopez', 'curp' => 'MALL900101MDFRPR01', 'rfc' => 'MALL900101AB1', 'correo' => 'laura@sisen.com', 'telefono' => '5551001001', 'departamento_id' => $departamentos[0]->id, 'puesto_id' => $puestos[0]->id, 'sueldo_base' => 26000],
            ['nombre' => 'Carlos', 'apellidos' => 'Rivera Santos', 'curp' => 'RISC880202HDFRNR02', 'rfc' => 'RISC880202CD2', 'correo' => 'carlos@sisen.com', 'telefono' => '5551001002', 'departamento_id' => $departamentos[1]->id, 'puesto_id' => $puestos[1]->id, 'sueldo_base' => 32000],
            ['nombre' => 'Miriam', 'apellidos' => 'Torres Vega', 'curp' => 'TOVM920303MDFRGR03', 'rfc' => 'TOVM920303EF3', 'correo' => 'miriam@sisen.com', 'telefono' => '5551001003', 'departamento_id' => $departamentos[2]->id, 'puesto_id' => $puestos[2]->id, 'sueldo_base' => 24500],
            ['nombre' => 'Andres', 'apellidos' => 'Gomez Diaz', 'curp' => 'GODA940404HDFMRN04', 'rfc' => 'GODA940404GH4', 'correo' => 'andres@sisen.com', 'telefono' => '5551001004', 'departamento_id' => $departamentos[3]->id, 'puesto_id' => $puestos[3]->id, 'sueldo_base' => 38000],
        ])->map(fn ($data) => Empleado::create($data + [
            'direccion' => 'Av. Empresa 123, Ciudad de Mexico',
            'fecha_nacimiento' => '1990-01-01',
            'fecha_contratacion' => '2024-01-15',
            'estado' => 'activo',
        ]));

        $usuarios = [
            ['name' => 'Administrador SISEN', 'email' => 'admin@sisen.com', 'role' => 'Administrador', 'empleado_id' => null],
            ['name' => 'Recursos Humanos', 'email' => 'rh@sisen.com', 'role' => 'Recursos Humanos', 'empleado_id' => $empleados[0]->id],
            ['name' => 'Contador', 'email' => 'contador@sisen.com', 'role' => 'Contador', 'empleado_id' => $empleados[1]->id],
            ['name' => 'Empleado Demo', 'email' => 'empleado@sisen.com', 'role' => 'Empleado', 'empleado_id' => $empleados[3]->id],
        ];

        foreach ($usuarios as $data) {
            User::create($data + ['password' => 'password', 'estado' => 'activo']);
        }

        foreach ($empleados as $index => $empleado) {
            $nomina = [
                'empleado_id' => $empleado->id,
                'periodo_pago' => 'Primera quincena mayo 2026',
                'fecha_pago' => '2026-05-15',
                'sueldo_base' => $empleado->sueldo_base / 2,
                'bonos' => 1200 + ($index * 250),
                'horas_extra' => 500,
                'deducciones' => 250,
                'isr' => 900 + ($index * 100),
                'imss' => 450,
                'estado' => $index % 2 === 0 ? 'pagada' : 'pendiente',
            ];
            $nomina['total_pagar'] = Nomina::calcularTotal($nomina);
            Nomina::create($nomina);

            Asistencia::create(['empleado_id' => $empleado->id, 'fecha' => '2026-05-25', 'hora_entrada' => '08:00', 'hora_salida' => '17:00', 'estado' => 'presente']);
            Asistencia::create(['empleado_id' => $empleado->id, 'fecha' => '2026-05-26', 'hora_entrada' => '08:18', 'hora_salida' => '17:05', 'estado' => $index === 1 ? 'retardo' : 'presente']);
        }

        Permiso::create(['empleado_id' => $empleados[0]->id, 'tipo' => 'vacaciones', 'fecha_inicio' => '2026-06-03', 'fecha_fin' => '2026-06-07', 'motivo' => 'Periodo vacacional programado.', 'estado' => 'pendiente']);
        Permiso::create(['empleado_id' => $empleados[2]->id, 'tipo' => 'permiso', 'fecha_inicio' => '2026-05-30', 'fecha_fin' => '2026-05-30', 'motivo' => 'Tramite personal.', 'estado' => 'aprobado']);
    }
}
