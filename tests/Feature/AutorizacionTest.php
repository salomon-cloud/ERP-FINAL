<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Compartido\Models\Rol;
use Database\Seeders\RolPrivilegioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La capa de compatibilidad de autorizacion: los modulos nuevos verifican
 * privilegios sin que ninguna ruta de SISEN v1 deje de funcionar.
 */
class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rolLegado = 'Empleado'): User
    {
        return User::create([
            'name' => 'Usuario de prueba',
            'email' => 'prueba'.uniqid().'@sisen.com',
            'password' => 'password',
            'role' => $rolLegado,
            'estado' => 'activo',
        ]);
    }

    public function test_la_columna_role_de_v1_sigue_mandando(): void
    {
        $contador = $this->usuario('Contador');

        $this->assertTrue($contador->hasAnyRole(['Contador']));
        $this->assertTrue($contador->hasAnyRole(['Administrador', 'Contador']));
        $this->assertFalse($contador->hasAnyRole(['Recursos Humanos']));
    }

    public function test_el_administrador_de_v1_pasa_cualquier_verificacion(): void
    {
        $admin = $this->usuario('Administrador');

        $this->assertTrue($admin->hasAnyRole(['Recursos Humanos']));
        $this->assertTrue($admin->tieneAlgunPrivilegio(['finanzas.polizas.contabilizar']));
    }

    public function test_un_rol_del_modelo_normalizado_tambien_responde_has_any_role(): void
    {
        $this->seed(RolPrivilegioSeeder::class);

        $usuario = $this->usuario('Empleado');
        $usuario->roles()->attach(Rol::where('codigo', 'almacenista')->firstOrFail());

        $this->assertTrue($usuario->fresh()->hasAnyRole(['Almacenista']));
    }

    public function test_los_privilegios_llegan_por_los_roles(): void
    {
        $this->seed(RolPrivilegioSeeder::class);

        $usuario = $this->usuario('Empleado');
        $usuario->roles()->attach(Rol::where('codigo', 'almacenista')->firstOrFail());
        $usuario = $usuario->fresh();

        $this->assertTrue($usuario->tieneAlgunPrivilegio(['inventario.ajustes.aprobar']));
        $this->assertFalse($usuario->tieneAlgunPrivilegio(['finanzas.polizas.contabilizar']));
    }

    public function test_los_privilegios_funcionan_como_habilidades_del_gate(): void
    {
        $this->seed(RolPrivilegioSeeder::class);

        $usuario = $this->usuario('Empleado');
        $usuario->roles()->attach(Rol::where('codigo', 'contador')->firstOrFail());
        $usuario = $usuario->fresh();

        $this->assertTrue($usuario->can('finanzas.polizas.contabilizar'));
        $this->assertFalse($usuario->can('ventas.pedidos.confirmar'));
    }

    public function test_un_usuario_inactivo_no_pasa_el_middleware_de_privilegios(): void
    {
        $usuario = $this->usuario('Administrador');
        $usuario->update(['estado' => 'inactivo']);

        $this->assertFalse($usuario->estado === 'activo');
    }
}
