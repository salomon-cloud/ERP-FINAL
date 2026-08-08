<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Providers\ModuleServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * El esqueleto modular: agregar una carpeta bajo app/Modules debe bastar para
 * que el modulo tenga rutas, vistas, migraciones y menu.
 */
class ModulosTest extends TestCase
{
    use RefreshDatabase;

    private const MODULOS = ['Compartido', 'Finanzas', 'Inventario', 'RH', 'Ventas', 'Compras', 'CRM'];

    public function test_el_provider_descubre_los_siete_modulos(): void
    {
        $this->assertSame(
            collect(self::MODULOS)->sort()->values()->all(),
            array_keys(ModuleServiceProvider::modulos())
        );
    }

    public function test_cada_modulo_de_negocio_registra_su_ruta_de_tablero(): void
    {
        foreach (self::MODULOS as $modulo) {
            if ($modulo === 'Compartido') {
                continue;   // vive en la raiz y aun no expone paginas
            }

            $slug = ModuleServiceProvider::slug($modulo);

            $this->assertTrue(
                Route::has("{$slug}.dashboard"),
                "El modulo {$modulo} no registro la ruta [{$slug}.dashboard]."
            );
        }
    }

    public function test_cada_modulo_registra_su_namespace_de_vistas(): void
    {
        foreach (self::MODULOS as $modulo) {
            $slug = ModuleServiceProvider::slug($modulo);

            $this->assertTrue(
                view()->exists($slug.'::paginas.modulo-pendiente') || is_dir(app_path("Modules/{$modulo}/Views")),
                "El modulo {$modulo} no tiene su carpeta de vistas registrada."
            );
        }
    }

    public function test_el_tablero_de_un_modulo_exige_autenticacion(): void
    {
        $this->get('/finanzas')->assertRedirect('/login');
    }

    public function test_el_tablero_de_un_modulo_se_pinta_para_un_usuario_autenticado(): void
    {
        $usuario = User::create([
            'name' => 'Administrador de pruebas',
            'email' => 'admin.pruebas@sisen.com',
            'password' => 'password',
            'role' => 'Administrador',
            'estado' => 'activo',
        ]);

        $this->actingAs($usuario)
            ->get('/finanzas')
            ->assertOk()
            ->assertSee('Finanzas')
            ->assertSee('finanzas.dashboard');   // la pagina explica como sustituirla
    }

    public function test_las_rutas_de_los_modulos_no_usan_closures_y_soportan_cache(): void
    {
        foreach (Route::getRoutes() as $ruta) {
            $nombre = $ruta->getName() ?? '';

            if (! str_ends_with($nombre, '.dashboard') || $nombre === 'dashboard') {
                continue;
            }

            $this->assertIsString(
                $ruta->getAction('uses'),
                "La ruta [{$nombre}] usa un closure y rompe route:cache."
            );
        }
    }
}
