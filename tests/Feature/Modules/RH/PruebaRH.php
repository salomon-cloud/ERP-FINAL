<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Models\User;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Puesto;
use Database\Seeders\RolPrivilegioSeeder;
use Database\Seeders\SecuenciaDocumentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lo que comparten las pruebas del modulo: la base recien migrada, los roles
 * sembrados y unos cuantos ayudantes para no repetir el mismo alta doce veces.
 *
 * Se usa RefreshDatabase y no una transaccion a mano porque varias pruebas
 * ejercitan restricciones de la base (unicos, CHECK) y necesitan un esquema
 * limpio y predecible.
 */
abstract class PruebaRH extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolPrivilegioSeeder::class);
        $this->seed(SecuenciaDocumentoSeeder::class);

        $this->admin = $this->usuarioConRol('Administrador');
    }

    /** Un usuario con el rol de v1 indicado. Administrador pasa todo privilegio. */
    protected function usuarioConRol(string $rol, ?string $correo = null): User
    {
        return User::create([
            'name' => "Usuario {$rol}",
            'email' => $correo ?? strtolower(str_replace(' ', '.', $rol)).'@pruebas.test',
            'password' => 'password',
            'role' => $rol,
            'estado' => 'activo',
        ]);
    }

    protected function organizacion(string $codigo = 'PRUEBA'): int
    {
        return (int) DB::table('organizaciones')->insertGetId([
            'codigo' => $codigo,
            'nombre' => 'Organizacion de pruebas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function departamento(array $atributos = []): Departamento
    {
        return Departamento::create(array_merge([
            'codigo' => 'DEP-'.fake()->unique()->numerify('###'),
            'nombre' => 'Departamento '.fake()->unique()->word(),
            'estado' => 'activo',
        ], $atributos));
    }

    protected function puesto(Departamento $departamento, array $atributos = []): Puesto
    {
        return Puesto::create(array_merge([
            'departamento_id' => $departamento->id,
            'codigo' => 'PUE-'.fake()->unique()->numerify('###'),
            'nombre' => 'Puesto '.fake()->unique()->word(),
            'sueldo_minimo' => 10000,
            'sueldo_maximo' => 40000,
            'estado' => 'activo',
        ], $atributos));
    }

    protected function empleado(array $atributos = []): Empleado
    {
        $departamento = isset($atributos['departamento_id'])
            ? Departamento::find($atributos['departamento_id'])
            : $this->departamento();

        $puesto = isset($atributos['puesto_id'])
            ? Puesto::find($atributos['puesto_id'])
            : $this->puesto($departamento);

        return Empleado::create(array_merge([
            'departamento_id' => $departamento->id,
            'puesto_id' => $puesto->id,
            'numero_empleado' => fake()->unique()->numerify('EMP-####'),
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'curp' => strtoupper(fake()->unique()->bothify('??????##########')),
            'rfc' => strtoupper(fake()->unique()->bothify('????######??')),
            'correo' => fake()->unique()->safeEmail(),
            'fecha_nacimiento' => '1995-01-01',
            'fecha_contratacion' => '2024-01-01',
            'sueldo_base' => 30000,
            'frecuencia_pago' => 'quincenal',
            'estado' => 'activo',
        ], $atributos));
    }
}
