<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Modules\Compartido\Models\Privilegio;
use App\Modules\Compartido\Models\Rol;
use Illuminate\Support\Facades\Route;

/**
 * Que nadie entre a RH sin credencial.
 *
 * Es la prueba que PLANNING pide en el Apendice A.5: toda ruta nueva demuestra
 * auth y autorizacion.
 */
class AutorizacionTest extends PruebaRH
{
    public function test_las_rutas_de_rh_exigen_sesion(): void
    {
        $this->get('/rh')->assertRedirect('/login');
        $this->get('/rh/empleados')->assertRedirect('/login');
        $this->get('/rh/nomina-corridas')->assertRedirect('/login');
    }

    public function test_un_usuario_sin_privilegios_de_rh_recibe_403(): void
    {
        $empleado = $this->usuarioConRol('Empleado');

        $this->actingAs($empleado)->get('/rh')->assertForbidden();
        $this->actingAs($empleado)->get('/rh/empleados')->assertForbidden();
        $this->actingAs($empleado)->get('/rh/nominas')->assertForbidden();
        $this->actingAs($empleado)->get('/rh/reportes')->assertForbidden();
    }

    public function test_el_tablero_exige_privilegio_aunque_sea_la_entrada_del_modulo(): void
    {
        // La pagina provisional no lo exigia; esta pinta plantilla, ausencias y
        // nomina, asi que no puede quedar abierta a cualquier autenticado.
        $this->actingAs($this->usuarioConRol('Empleado'))->get('/rh')->assertForbidden();
        $this->actingAs($this->admin)->get('/rh')->assertOk();
    }

    public function test_recursos_humanos_entra_pero_no_a_finanzas(): void
    {
        $rh = $this->usuarioConRol('Recursos Humanos');
        $rh->roles()->sync(
            Rol::where('codigo', 'recursos_humanos')->pluck('id')
        );

        $this->actingAs($rh)->get('/rh/empleados')->assertOk();
    }

    public function test_ninguna_ruta_de_rh_queda_sin_privilegio(): void
    {
        $sinGuardia = collect(Route::getRoutes())
            ->filter(fn ($ruta) => str_starts_with((string) $ruta->getName(), 'rh.'))
            ->reject(fn ($ruta) => collect($ruta->gatherMiddleware())
                ->contains(fn ($m) => str_starts_with((string) $m, 'permission:')))
            ->map(fn ($ruta) => $ruta->getName())
            ->values();

        $this->assertTrue(
            $sinGuardia->isEmpty(),
            'Estas rutas de RH no exigen privilegio: '.$sinGuardia->implode(', ')
        );
    }

    public function test_los_privilegios_que_citan_las_rutas_existen_de_verdad(): void
    {
        $citados = collect(Route::getRoutes())
            ->filter(fn ($ruta) => str_starts_with((string) $ruta->getName(), 'rh.'))
            ->flatMap(fn ($ruta) => collect($ruta->gatherMiddleware())
                ->filter(fn ($m) => str_starts_with((string) $m, 'permission:'))
                ->flatMap(fn ($m) => explode(',', substr((string) $m, 11))))
            ->unique();

        $existentes = Privilegio::pluck('codigo');
        $inventados = $citados->diff($existentes);

        // Un privilegio inventado deja el modulo inaccesible sin avisar.
        $this->assertTrue(
            $inventados->isEmpty(),
            'Privilegios citados en rutas que no existen: '.$inventados->implode(', ')
        );
    }
}
