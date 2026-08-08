<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El criterio de salida del hito M0: nada de SISEN v1 se rompe.
 *
 * Las migraciones de los modulos ALTERAN las tablas de v1 (departamentos,
 * empleados, nominas, asistencias, permisos) y el modelo User cambio, asi que
 * estas pruebas cuidan que el login y las pantallas de siempre sigan igual.
 */
class RegresionV1Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrador SISEN',
            'email' => 'admin@sisen.com',
            'password' => 'password',
            'role' => 'Administrador',
            'estado' => 'activo',
        ]);
    }

    public function test_el_login_de_v1_sigue_funcionando(): void
    {
        $this->admin();

        $this->post('/login', ['email' => 'admin@sisen.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_el_tablero_de_v1_sigue_pintandose(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_el_crud_de_departamentos_de_v1_sigue_funcionando(): void
    {
        $this->actingAs($this->admin());

        $this->get('/departamentos')->assertOk();

        $this->post('/departamentos', [
            'nombre' => 'Produccion',
            'descripcion' => 'Planta y manufactura.',
            'responsable' => 'Jefe de planta',
            'estado' => 'activo',
        ])->assertRedirect();

        $this->assertDatabaseHas('departamentos', ['nombre' => 'Produccion']);
    }

    public function test_las_columnas_nuevas_de_rh_no_estorban_a_los_registros_de_v1(): void
    {
        // Un departamento creado como lo hace v1, sin ninguno de los campos
        // empresariales, sigue siendo valido.
        $departamento = Departamento::create([
            'nombre' => 'Calidad',
            'descripcion' => 'Aseguramiento de calidad.',
            'responsable' => 'Supervisor',
            'estado' => 'activo',
        ]);

        $this->assertNull($departamento->codigo);
        $this->assertNull($departamento->jefe_id);
        $this->assertDatabaseHas('departamentos', ['id' => $departamento->id, 'nombre' => 'Calidad']);
    }

    public function test_el_menu_lateral_muestra_los_modulos_nuevos_junto_a_los_de_v1(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Empleados')        // v1
            ->assertSee('Modulos ERP')      // seccion nueva
            ->assertSee('Inventario');      // modulo nuevo
    }
}
