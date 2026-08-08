<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Compartido\Models\Privilegio;
use App\Modules\Compartido\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Roles y privilegios base. NO son datos de demostracion: son el minimo que la
 * aplicacion necesita para autorizar, y por eso el seeder es idempotente
 * (updateOrCreate) y puede volver a correrse en produccion sin duplicar nada.
 *
 * Los codigos siguen <modulo>.<entidad>.<accion>, tal como los espera el
 * middleware `permission` y el Gate.
 */
class RolPrivilegioSeeder extends Seeder
{
    /**
     * Acciones estandar de cada entidad. Un modulo agrega las suyas propias
     * (contabilizar, aprobar, convertir) en la lista de abajo.
     */
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /** @var array<string, array<string, array<int, string>>> */
    private const ENTIDADES = [
        'finanzas' => [
            'cuentas' => ['administrar'],
            'polizas' => ['contabilizar', 'cancelar'],
            'periodos' => ['cerrar', 'reabrir'],
            'presupuestos' => ['aprobar'],
            'bancos' => ['conciliar'],
            'impuestos' => ['administrar'],
            'reportes' => [],
        ],
        'ventas' => [
            'clientes' => [],
            'cotizaciones' => ['convertir'],
            'pedidos' => ['confirmar', 'cancelar'],
            'facturas' => ['emitir', 'cancelar'],
            'notas_credito' => ['emitir'],
            'cobros' => ['aplicar'],
            'descuentos' => ['autorizar'],
            'reportes' => [],
        ],
        'compras' => [
            'proveedores' => [],
            'requisiciones' => ['aprobar'],
            'ordenes' => ['confirmar', 'cancelar'],
            'recepciones' => ['aplicar'],
            'facturas' => ['contabilizar'],
            'pagos' => ['aplicar'],
            'reportes' => [],
        ],
        'inventario' => [
            'productos' => [],
            'almacenes' => [],
            'existencias' => [],
            'movimientos' => [],
            'traspasos' => ['aprobar'],
            'ajustes' => ['aprobar'],
            'conteos' => ['cerrar'],
            'reportes' => [],
        ],
        'rh' => [
            'empleados' => [],
            'departamentos' => [],
            'puestos' => [],
            'asistencias' => [],
            'permisos' => ['aprobar'],
            'nomina' => ['procesar', 'aplicar'],
            'contratos' => [],
            'organigrama' => ['administrar'],
            'reportes' => [],
        ],
        'crm' => [
            'prospectos' => ['convertir'],
            'oportunidades' => ['cambiar_etapa'],
            'contactos' => [],
            'empresas' => [],
            'actividades' => [],
            'tareas' => [],
            'reportes' => [],
        ],
        'compartido' => [
            'usuarios' => [],
            'roles' => ['administrar'],
            'catalogos' => ['administrar'],
            'configuraciones' => ['administrar'],
            'bitacora' => [],
            'adjuntos' => [],
            'notificaciones' => [],
        ],
    ];

    /**
     * Roles base y los modulos sobre los que mandan. `Administrador` no aparece
     * porque pasa todas las verificaciones por definicion.
     *
     * @var array<string, array{nombre: string, modulos: array<int, string>}>
     */
    private const ROLES = [
        'administrador' => ['nombre' => 'Administrador', 'modulos' => ['*']],
        'recursos_humanos' => ['nombre' => 'Recursos Humanos', 'modulos' => ['rh']],
        'contador' => ['nombre' => 'Contador', 'modulos' => ['finanzas']],
        'ventas' => ['nombre' => 'Ventas', 'modulos' => ['ventas', 'crm']],
        'compras' => ['nombre' => 'Compras', 'modulos' => ['compras']],
        'almacenista' => ['nombre' => 'Almacenista', 'modulos' => ['inventario']],
        'empleado' => ['nombre' => 'Empleado', 'modulos' => []],
    ];

    public function run(): void
    {
        $privilegios = $this->sembrarPrivilegios();

        foreach (self::ROLES as $codigo => $datos) {
            $rol = Rol::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $datos['nombre'], 'es_sistema' => true]
            );

            $rol->privilegios()->sync($this->privilegiosDe($datos['modulos'], $privilegios));
        }
    }

    /** @return array<string, int> codigo => id */
    private function sembrarPrivilegios(): array
    {
        $mapa = [];

        foreach (self::ENTIDADES as $modulo => $entidades) {
            foreach ($entidades as $entidad => $accionesExtra) {
                foreach ([...self::ACCIONES, ...$accionesExtra] as $accion) {
                    $codigo = "{$modulo}.{$entidad}.{$accion}";

                    $mapa[$codigo] = Privilegio::updateOrCreate(
                        ['codigo' => $codigo],
                        ['modulo' => $modulo]
                    )->id;
                }
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, string>  $modulos
     * @param  array<string, int>  $privilegios
     * @return array<int, int>
     */
    private function privilegiosDe(array $modulos, array $privilegios): array
    {
        if ($modulos === ['*']) {
            return array_values($privilegios);
        }

        $asignados = [];

        foreach ($privilegios as $codigo => $id) {
            $modulo = explode('.', $codigo)[0];

            if (in_array($modulo, $modulos, true)) {
                $asignados[] = $id;
            }
        }

        return $asignados;
    }
}
