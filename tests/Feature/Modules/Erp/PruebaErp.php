<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Models\User;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\UnidadMedida;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use App\Modules\Ventas\Models\Cliente;
use Database\Seeders\CatalogoSeeder;
use Database\Seeders\RolPrivilegioSeeder;
use Database\Seeders\SecuenciaDocumentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La base de las pruebas de Ventas, Compras e Inventario.
 *
 * Los tres modulos son UN solo flujo -- una venta descuenta lo que una compra
 * metio -- asi que comparten base de pruebas en vez de tener tres casi
 * identicas. Es el equivalente de PruebaRH para la cadena comercial.
 *
 * Se usa RefreshDatabase y no una transaccion a mano porque varias pruebas
 * ejercitan restricciones de la base (CHECK, unicos que ignoran el borrado
 * logico) y necesitan un esquema limpio y predecible.
 */
abstract class PruebaErp extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolPrivilegioSeeder::class);
        $this->seed(CatalogoSeeder::class);
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

    /**
     * Un usuario SIN privilegios de v1, con el rol normalizado indicado.
     *
     * Es lo que hace falta para probar los 403: `role` en blanco para que la
     * capa de compatibilidad de v1 no lo deje pasar, y el rol de verdad por
     * usuario_roles.
     */
    protected function usuarioConRolNormalizado(string $codigoRol, string $correo): User
    {
        $usuario = User::create([
            'name' => "Usuario {$codigoRol}",
            'email' => $correo,
            'password' => 'password',
            'role' => 'Empleado',
            'estado' => 'activo',
        ]);

        $rolId = DB::table('roles')->where('codigo', $codigoRol)->value('id');

        if ($rolId !== null) {
            // attach() y no un insert a mano: la tabla pivote solo tiene
            // created_at (lo pone la base con useCurrent), sin updated_at.
            $usuario->roles()->attach($rolId);
        }

        return $usuario;
    }

    protected function almacen(array $atributos = []): Almacen
    {
        return Almacen::create(array_merge([
            'codigo' => 'ALM-'.fake()->unique()->numerify('###'),
            'nombre' => 'Almacen '.fake()->unique()->word(),
        ], $atributos));
    }

    protected function unidadBase(): UnidadMedida
    {
        return UnidadMedida::firstOrCreate(
            ['codigo' => 'PZA'],
            ['nombre' => 'Pieza', 'factor_base' => 1, 'es_base' => true],
        );
    }

    protected function categoria(array $atributos = []): CategoriaProducto
    {
        return CategoriaProducto::create(array_merge([
            'codigo' => 'CAT-'.fake()->unique()->numerify('###'),
            'nombre' => 'Categoria '.fake()->unique()->word(),
        ], $atributos));
    }

    protected function producto(array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'nombre' => 'Producto '.fake()->unique()->words(2, true),
            'unidad_id' => $this->unidadBase()->id,
            'costo' => 100,
            'precio_venta' => 150,
            'estado' => 'activo',
        ], $atributos));
    }

    protected function cliente(array $atributos = []): Cliente
    {
        return Cliente::create(array_merge([
            'codigo' => 'CLI-'.fake()->unique()->numerify('####'),
            'nombre' => fake()->company(),
            'moneda' => 'MXN',
            'estado' => 'activo',
        ], $atributos));
    }

    protected function proveedor(array $atributos = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'codigo' => 'PRV-'.fake()->unique()->numerify('####'),
            'nombre' => fake()->company(),
            'moneda' => 'MXN',
            'estado' => 'activo',
        ], $atributos));
    }

    /**
     * Mete existencia inicial sin pasar por una compra.
     *
     * Es el unico lugar de todo el sistema donde se usa el tipo `inicial`: sirve
     * para preparar el escenario de una prueba sin tener que capturar una orden
     * de compra completa antes de cada venta.
     */
    protected function cargarExistencia(Producto $producto, Almacen $almacen, float $cantidad, float $costo = 100): void
    {
        app(ServicioMovimientoInventario::class)->registrar(
            TipoMovimiento::Inicial,
            (int) $producto->id,
            (int) $almacen->id,
            $cantidad,
            $costo,
        );
    }

    /** El disponible de un producto en un almacen, para asertar sobre el. */
    protected function disponible(Producto $producto, Almacen $almacen): float
    {
        return app(ServicioExistencias::class)
            ->disponible((int) $producto->id, (int) $almacen->id);
    }
}
