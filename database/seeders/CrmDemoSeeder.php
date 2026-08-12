<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\CRM\Enums\EtapaOportunidad;
use App\Modules\CRM\Enums\EstadoProspecto;
use App\Modules\CRM\Enums\EstadoTarea;
use App\Modules\CRM\Enums\OrigenProspecto;
use App\Modules\CRM\Enums\PrioridadTarea;
use App\Modules\CRM\Enums\TipoActividadCrm;
use App\Modules\CRM\Models\Actividad;
use App\Modules\CRM\Models\Contacto;
use App\Modules\CRM\Models\Empresa;
use App\Modules\CRM\Models\NotaCrm;
use App\Modules\CRM\Models\Oportunidad;
use App\Modules\CRM\Models\Prospecto;
use App\Modules\CRM\Models\Tarea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrmDemoSeeder extends Seeder
{
    private const PASSWORD_DEMO = 'password';

    public function run(): void
    {
        $usuarios = $this->sembrarUsuarios();
        $empresa = $this->sembrarEmpresas($usuarios['admin']);
        $prospectos = $this->sembrarProspectos($usuarios);
        $contactos = $this->sembrarContactos($empresa, $prospectos);
        $oportunidades = $this->sembrarOportunidades($prospectos, $contactos, $usuarios);

        $this->sembrarActividades($empresa, $prospectos, $oportunidades, $usuarios);
        $this->sembrarTareas($empresa, $prospectos, $oportunidades, $usuarios);
        $this->sembrarNotas($empresa, $prospectos, $oportunidades, $usuarios);

        $this->command?->info('Datos demo de CRM sembrados.');
    }

    /** @return array<string, User> */
    private function sembrarUsuarios(): array
    {
        $usuarios = [
            'admin@sisen.com' => ['name' => 'Administrador SISEN', 'role' => 'Administrador'],
            'crm@sisen.com' => ['name' => 'Equipo CRM', 'role' => 'Contador'],
            'ventas@sisen.com' => ['name' => 'Ventas Demo', 'role' => 'Contador'],
        ];

        $mapa = [];

        foreach ($usuarios as $email => $datos) {
            if ($email === 'admin@sisen.com') {
                $clave = 'admin';
            } elseif ($email === 'crm@sisen.com') {
                $clave = 'crm';
            } else {
                $clave = 'ventas';
            }

            $mapa[$clave] = User::updateOrCreate(
                ['email' => $email],
                $datos + [
                    'password' => Hash::make(self::PASSWORD_DEMO),
                    'estado' => 'activo',
                    'debe_cambiar_password' => false,
                ]
            );
        }

        return $mapa;
    }

    private function sembrarEmpresas(User $admin): Empresa
    {
        return Empresa::updateOrCreate(
            ['nombre' => 'Grupo Delta Salud'],
            [
                'organizacion_id' => null,
                'rfc' => 'GDS010101AA1',
                'giro' => 'Servicios de salud',
                'sitio_web' => 'https://delta-salud.test',
                'correo' => 'contacto@delta-salud.test',
                'telefono' => '5552001001',
                'direccion' => 'Av. Corporativa 100, Ciudad de Mexico',
                'estado' => 'activo',
                'creado_por' => $admin->id,
                'actualizado_por' => $admin->id,
            ]
        );
    }

    /** @return array<string, Prospecto> */
    private function sembrarProspectos(array $usuarios): array
    {
        $prospectos = [
            'ana' => [
                'origen' => OrigenProspecto::Web->value,
                'nombre' => 'Ana',
                'apellidos' => 'Solis',
                'empresa_nombre' => 'Clinica San Pedro',
                'correo' => 'ana@sanpedro.test',
                'telefono' => '5553001001',
                'estado' => EstadoProspecto::Calificado->value,
                'asignado_a' => $usuarios['crm']->id,
                'valor_estimado' => 120000,
                'notas' => 'Pide una propuesta de servicio mensual.',
                'motivo_perdida' => null,
            ],
            'luis' => [
                'origen' => OrigenProspecto::Referido->value,
                'nombre' => 'Luis',
                'apellidos' => 'Ramirez',
                'empresa_nombre' => 'Laboratorio del Norte',
                'correo' => 'luis@laboratorionorte.test',
                'telefono' => '5553001002',
                'estado' => EstadoProspecto::Contactado->value,
                'asignado_a' => $usuarios['ventas']->id,
                'valor_estimado' => 85000,
                'notas' => 'Interesado en cotizacion de insumos recurrentes.',
                'motivo_perdida' => null,
            ],
            'marta' => [
                'origen' => OrigenProspecto::Evento->value,
                'nombre' => 'Marta',
                'apellidos' => 'Vega',
                'empresa_nombre' => 'Consultoria Integral',
                'correo' => 'marta@consultoriaintegral.test',
                'telefono' => '5553001003',
                'estado' => EstadoProspecto::Perdido->value,
                'asignado_a' => $usuarios['crm']->id,
                'valor_estimado' => 56000,
                'notas' => 'No se concreto por presupuesto.',
                'motivo_perdida' => 'Se pospuso la compra para el siguiente trimestre.',
            ],
        ];

        $mapa = [];

        foreach ($prospectos as $clave => $datos) {
            $mapa[$clave] = Prospecto::updateOrCreate(
                ['correo' => $datos['correo']],
                $datos + [
                    'organizacion_id' => null,
                    'cliente_convertido_id' => null,
                    'convertido_en' => null,
                ]
            );
        }

        return $mapa;
    }

    /** @return array<string, Contacto> */
    private function sembrarContactos(Empresa $empresa, array $prospectos): array
    {
        $contactos = [
            'principal' => [
                'empresa_id' => $empresa->id,
                'prospecto_id' => $prospectos['ana']->id,
                'nombre' => 'Ana',
                'apellidos' => 'Solis',
                'correo' => 'ana@sanpedro.test',
                'telefono' => '5553001001',
                'puesto' => 'Gerente de compras',
                'es_principal' => true,
            ],
            'operacion' => [
                'empresa_id' => $empresa->id,
                'prospecto_id' => $prospectos['luis']->id,
                'nombre' => 'Luis',
                'apellidos' => 'Ramirez',
                'correo' => 'luis@laboratorionorte.test',
                'telefono' => '5553001002',
                'puesto' => 'Coordinador operativo',
                'es_principal' => false,
            ],
        ];

        $mapa = [];

        foreach ($contactos as $clave => $datos) {
            $mapa[$clave] = Contacto::updateOrCreate(
                ['correo' => $datos['correo'], 'empresa_id' => $datos['empresa_id']],
                $datos + ['cliente_id' => null]
            );
        }

        return $mapa;
    }

    /** @return array<string, Oportunidad> */
    private function sembrarOportunidades(array $prospectos, array $contactos, array $usuarios): array
    {
        $oportunidades = [
            'servicio' => [
                'prospecto_id' => $prospectos['ana']->id,
                'contacto_id' => $contactos['principal']->id,
                'nombre' => 'Servicio corporativo anual',
                'descripcion' => 'Propuesta de servicio integral para clinica privada.',
                'etapa' => EtapaOportunidad::Propuesta->value,
                'monto' => 120000,
                'probabilidad' => 62.5,
                'fecha_cierre_estimada' => now()->addWeeks(3)->toDateString(),
                'asignado_a' => $usuarios['crm']->id,
                'pedido_id' => null,
                'ganada_en' => null,
                'perdida_en' => null,
                'motivo_perdida' => null,
            ],
            'insumos' => [
                'prospecto_id' => $prospectos['luis']->id,
                'contacto_id' => $contactos['operacion']->id,
                'nombre' => 'Compra recurrente de insumos',
                'descripcion' => 'Posible convenio por volumen de compra.',
                'etapa' => EtapaOportunidad::Negociacion->value,
                'monto' => 85000,
                'probabilidad' => 78.0,
                'fecha_cierre_estimada' => now()->addWeeks(2)->toDateString(),
                'asignado_a' => $usuarios['ventas']->id,
                'pedido_id' => null,
                'ganada_en' => null,
                'perdida_en' => null,
                'motivo_perdida' => null,
            ],
            'cerrada' => [
                'prospecto_id' => $prospectos['marta']->id,
                'contacto_id' => null,
                'nombre' => 'Proyecto descartado',
                'descripcion' => 'Se cerro sin conversion por falta de presupuesto.',
                'etapa' => EtapaOportunidad::Perdida->value,
                'monto' => 56000,
                'probabilidad' => 0,
                'fecha_cierre_estimada' => now()->subWeeks(1)->toDateString(),
                'asignado_a' => $usuarios['crm']->id,
                'pedido_id' => null,
                'ganada_en' => null,
                'perdida_en' => now()->subDays(3),
                'motivo_perdida' => 'Se decidio pausar la inversion.',
            ],
        ];

        $mapa = [];

        foreach ($oportunidades as $clave => $datos) {
            $mapa[$clave] = Oportunidad::updateOrCreate(
                ['nombre' => $datos['nombre']],
                $datos + ['organizacion_id' => null, 'cliente_id' => null]
            );
        }

        return $mapa;
    }

    private function sembrarActividades(Empresa $empresa, array $prospectos, array $oportunidades, array $usuarios): void
    {
        $actividades = [
            ['tipo_actividad' => TipoActividadCrm::Llamada->value, 'entidad_tipo' => Empresa::class, 'entidad_id' => $empresa->id, 'resumen' => 'Llamada de descubrimiento con la empresa.', 'resultado' => 'Interes inicial confirmado.', 'programada_en' => now()->subDays(2), 'completada_en' => now()->subDays(2)->addHours(2), 'asignada_a' => $usuarios['crm']->id],
            ['tipo_actividad' => TipoActividadCrm::Correo->value, 'entidad_tipo' => Prospecto::class, 'entidad_id' => $prospectos['ana']->id, 'resumen' => 'Enviada propuesta comercial.', 'resultado' => 'Recibida y revisada.', 'programada_en' => now()->subDay(), 'completada_en' => now()->subDay()->addHours(1), 'asignada_a' => $usuarios['ventas']->id],
            ['tipo_actividad' => TipoActividadCrm::Reunion->value, 'entidad_tipo' => Oportunidad::class, 'entidad_id' => $oportunidades['servicio']->id, 'resumen' => 'Reunion de negociacion.', 'resultado' => 'Pendiente de aprobacion interna.', 'programada_en' => now()->addDay(), 'completada_en' => null, 'asignada_a' => $usuarios['crm']->id],
        ];

        foreach ($actividades as $actividad) {
            Actividad::updateOrCreate(
                ['resumen' => $actividad['resumen'], 'entidad_tipo' => $actividad['entidad_tipo'], 'entidad_id' => $actividad['entidad_id']],
                $actividad
            );
        }
    }

    private function sembrarTareas(Empresa $empresa, array $prospectos, array $oportunidades, array $usuarios): void
    {
        $tareas = [
            ['titulo' => 'Dar seguimiento a propuesta', 'entidad_tipo' => Oportunidad::class, 'entidad_id' => $oportunidades['servicio']->id, 'asignada_a' => $usuarios['ventas']->id, 'fecha_limite' => now()->addDays(2)->toDateString(), 'prioridad' => PrioridadTarea::Alta->value, 'estado' => EstadoTarea::Pendiente->value, 'completada_en' => null],
            ['titulo' => 'Actualizar datos de empresa', 'entidad_tipo' => Empresa::class, 'entidad_id' => $empresa->id, 'asignada_a' => $usuarios['crm']->id, 'fecha_limite' => now()->addDays(4)->toDateString(), 'prioridad' => PrioridadTarea::Media->value, 'estado' => EstadoTarea::EnProceso->value, 'completada_en' => null],
            ['titulo' => 'Cerrar oportunidad perdida', 'entidad_tipo' => Prospecto::class, 'entidad_id' => $prospectos['marta']->id, 'asignada_a' => $usuarios['crm']->id, 'fecha_limite' => now()->subDay()->toDateString(), 'prioridad' => PrioridadTarea::Baja->value, 'estado' => EstadoTarea::Completada->value, 'completada_en' => now()->subDay()],
        ];

        foreach ($tareas as $tarea) {
            Tarea::updateOrCreate(
                ['titulo' => $tarea['titulo']],
                $tarea + ['descripcion' => 'Seeding demo.']
            );
        }
    }

    private function sembrarNotas(Empresa $empresa, array $prospectos, array $oportunidades, array $usuarios): void
    {
        $notas = [
            ['entidad_tipo' => Empresa::class, 'entidad_id' => $empresa->id, 'autor_id' => $usuarios['crm']->id, 'cuerpo' => 'Empresa prioritaria para seguimiento comercial.', 'fijada' => true],
            ['entidad_tipo' => Prospecto::class, 'entidad_id' => $prospectos['ana']->id, 'autor_id' => $usuarios['ventas']->id, 'cuerpo' => 'Solicito cotizacion con vigencia de 15 dias.', 'fijada' => false],
            ['entidad_tipo' => Oportunidad::class, 'entidad_id' => $oportunidades['servicio']->id, 'autor_id' => $usuarios['crm']->id, 'cuerpo' => 'Negociacion en fase de propuesta.', 'fijada' => false],
        ];

        foreach ($notas as $nota) {
            NotaCrm::updateOrCreate(
                ['entidad_tipo' => $nota['entidad_tipo'], 'entidad_id' => $nota['entidad_id'], 'cuerpo' => $nota['cuerpo']],
                $nota
            );
        }
    }
}
