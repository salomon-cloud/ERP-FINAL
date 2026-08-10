<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Models\User;
use App\Modules\Compartido\Models\Privilegio;
use App\Modules\Compartido\Models\Rol;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Inventario\Models\AjusteInventario;
use App\Modules\Ventas\Models\Pedido;
use PHPUnit\Framework\Attributes\Test;

/**
 * El DOBLE FRENO de privilegios: leer no es actuar.
 *
 * El middleware `permission:` de cada ruta pide el privilegio amplio para ver, y
 * uno propio para cada accion de ciclo de vida. Estas pruebas comprueban las
 * dos mitades: que quien no tiene nada del modulo recibe 403, y que quien puede
 * VER un documento no puede por eso APLICARLO.
 */
class AutorizacionTest extends PruebaErp
{
    #[Test]
    public function un_usuario_sin_privilegios_no_entra_a_ningun_modulo(): void
    {
        $empleado = $this->usuarioConRolNormalizado('empleado', 'empleado@pruebas.test');

        $this->actingAs($empleado);

        foreach (['inventario.dashboard', 'compras.dashboard', 'ventas.dashboard'] as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }
    }

    #[Test]
    public function el_rol_de_ventas_no_entra_a_compras_ni_a_inventario(): void
    {
        $vendedor = $this->usuarioConRolNormalizado('ventas', 'vendedor@pruebas.test');

        $this->actingAs($vendedor);

        $this->get(route('ventas.pedidos.index'))->assertOk();
        $this->get(route('ventas.clientes.index'))->assertOk();

        $this->get(route('compras.ordenes.index'))->assertForbidden();
        $this->get(route('inventario.productos.index'))->assertForbidden();
    }

    #[Test]
    public function el_almacenista_entra_a_inventario_pero_no_a_ventas(): void
    {
        $almacenista = $this->usuarioConRolNormalizado('almacenista', 'almacenista@pruebas.test');

        $this->actingAs($almacenista);

        $this->get(route('inventario.existencias.index'))->assertOk();
        $this->get(route('inventario.movimientos.index'))->assertOk();

        $this->get(route('ventas.facturas.index'))->assertForbidden();
        $this->get(route('compras.proveedores.index'))->assertForbidden();
    }

    #[Test]
    public function el_rol_de_compras_ve_una_orden_pero_no_la_confirma_sin_su_privilegio(): void
    {
        // El rol `compras` que siembra RolPrivilegioSeeder SI trae
        // compras.ordenes.confirmar. Para probar el doble freno hace falta un
        // rol que solo pueda leer, asi que se arma uno a mano.
        $lector = $this->usuarioConRolSoloLectura('compras.ordenes.ver', 'lector.compras@pruebas.test');

        $orden = OrdenCompra::create([
            'proveedor_id' => $this->proveedor()->id,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($lector);

        $this->get(route('compras.ordenes.index'))->assertOk();
        $this->get(route('compras.ordenes.show', $orden))->assertOk();

        // Ver no es confirmar.
        $this->post(route('compras.ordenes.confirmar', $orden), ['version_fila' => 1])
            ->assertForbidden();
    }

    #[Test]
    public function ver_un_pedido_no_alcanza_para_confirmarlo(): void
    {
        $lector = $this->usuarioConRolSoloLectura('ventas.pedidos.ver', 'lector.ventas@pruebas.test');

        $pedido = Pedido::create([
            'cliente_id' => $this->cliente()->id,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($lector);

        $this->get(route('ventas.pedidos.show', $pedido))->assertOk();

        $this->post(route('ventas.pedidos.confirmar', $pedido), ['version_fila' => 1])
            ->assertForbidden();
        $this->post(route('ventas.pedidos.cancelar', $pedido))->assertForbidden();
    }

    #[Test]
    public function ver_un_ajuste_no_alcanza_para_aplicarlo(): void
    {
        $lector = $this->usuarioConRolSoloLectura('inventario.ajustes.ver', 'lector.inventario@pruebas.test');

        $ajuste = AjusteInventario::create(['motivo' => 'Prueba de autorizacion']);

        $this->actingAs($lector);

        $this->get(route('inventario.ajustes.show', $ajuste))->assertOk();

        // Aplicar un ajuste mueve inventario sin documento comercial detras, y
        // por eso exige `inventario.ajustes.aprobar`.
        $this->post(route('inventario.ajustes.aplicar', $ajuste))->assertForbidden();
    }

    #[Test]
    public function el_administrador_pasa_todo(): void
    {
        $this->actingAs($this->admin);

        foreach (['inventario.dashboard', 'compras.dashboard', 'ventas.dashboard'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    /**
     * Un usuario con UN solo privilegio, para probar el doble freno.
     *
     * Los roles sembrados dan el modulo completo, asi que no sirven para
     * distinguir "puede ver" de "puede aplicar": hace falta un rol a la medida.
     */
    private function usuarioConRolSoloLectura(string $privilegio, string $correo): User
    {
        $usuario = $this->usuarioConRol('Empleado', $correo);

        $rol = Rol::create([
            'codigo' => 'solo_lectura_'.substr(md5($privilegio), 0, 20),
            'nombre' => 'Solo lectura '.$privilegio,
            'es_sistema' => false,
        ]);

        $rol->privilegios()->attach(
            Privilegio::where('codigo', $privilegio)->value('id')
        );

        $usuario->roles()->attach($rol->id);

        return $usuario;
    }
}
