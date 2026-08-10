<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Modules\Compras\Models\DevolucionCompra;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Pago;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\Compras\Models\Requisicion;
use App\Modules\Inventario\Models\AjusteInventario;
use App\Modules\Inventario\Models\ConteoInventario;
use App\Modules\Inventario\Models\ReglaReorden;
use App\Modules\Inventario\Models\Traspaso;
use App\Modules\Ventas\Models\Cobro;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Models\NotaCredito;
use App\Modules\Ventas\Models\Pedido;
use Database\Seeders\DatosDemoErpSeeder;
use PHPUnit\Framework\Attributes\Test;

/**
 * Que TODA pantalla de los tres modulos abre sin romperse.
 *
 * Es la red de seguridad barata que atrapa lo que las pruebas de servicio no
 * ven: una variable que el controlador no paso, una relacion mal escrita en un
 * Blade, una ruta con el parametro cambiado. Nada de eso aparece hasta que
 * alguien entra a la pagina.
 *
 * Se recorren tambien las fichas de un documento REAL de cada tipo, no solo los
 * listados vacios: la mitad de los errores de vista viven en el `show`.
 */
class PantallasTest extends PruebaErp
{
    #[Test]
    public function los_tres_tableros_abren(): void
    {
        $this->actingAs($this->admin);

        foreach (['inventario.dashboard', 'compras.dashboard', 'ventas.dashboard'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    #[Test]
    public function los_listados_de_inventario_abren(): void
    {
        $this->actingAs($this->admin);

        $rutas = [
            'inventario.productos.index', 'inventario.categorias.index', 'inventario.unidades.index',
            'inventario.almacenes.index', 'inventario.ubicaciones.index', 'inventario.existencias.index',
            'inventario.movimientos.index', 'inventario.traspasos.index', 'inventario.ajustes.index',
            'inventario.conteos.index', 'inventario.reglas-reorden.index', 'inventario.reportes.index',
            'inventario.reportes.existencias', 'inventario.reportes.movimientos',
            'inventario.reportes.stock-bajo', 'inventario.reportes.valoracion',
            'inventario.reportes.caducidades',
        ];

        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    #[Test]
    public function los_listados_de_compras_abren(): void
    {
        $this->actingAs($this->admin);

        $rutas = [
            'compras.proveedores.index', 'compras.requisiciones.index', 'compras.ordenes.index',
            'compras.recepciones.index', 'compras.facturas.index', 'compras.devoluciones.index',
            'compras.pagos.index', 'compras.reportes.index', 'compras.reportes.por-periodo',
            'compras.reportes.por-proveedor', 'compras.reportes.por-producto',
            'compras.reportes.pendientes-por-recibir', 'compras.reportes.cuentas-por-pagar',
        ];

        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    #[Test]
    public function los_listados_de_ventas_abren(): void
    {
        $this->actingAs($this->admin);

        $rutas = [
            'ventas.clientes.index', 'ventas.listas-precios.index', 'ventas.cotizaciones.index',
            'ventas.pedidos.index', 'ventas.facturas.index', 'ventas.notas-credito.index',
            'ventas.cobros.index', 'ventas.reportes.index', 'ventas.reportes.por-periodo',
            'ventas.reportes.por-cliente', 'ventas.reportes.por-producto',
            'ventas.reportes.cuentas-por-cobrar', 'ventas.reportes.devoluciones',
        ];

        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    #[Test]
    public function los_formularios_de_alta_abren(): void
    {
        $this->actingAs($this->admin);

        $rutas = [
            'inventario.productos.create', 'inventario.categorias.create', 'inventario.unidades.create',
            'inventario.almacenes.create', 'inventario.ubicaciones.create', 'inventario.traspasos.create',
            'inventario.ajustes.create', 'inventario.conteos.create', 'inventario.reglas-reorden.create',
            'compras.proveedores.create', 'compras.requisiciones.create', 'compras.ordenes.create',
            'compras.recepciones.create', 'compras.facturas.create', 'compras.devoluciones.create',
            'compras.pagos.create',
            'ventas.clientes.create', 'ventas.listas-precios.create', 'ventas.cotizaciones.create',
            'ventas.pedidos.create', 'ventas.facturas.create', 'ventas.notas-credito.create',
            'ventas.cobros.create',
        ];

        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    #[Test]
    public function las_fichas_de_los_catalogos_abren(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 10);

        $this->get(route('inventario.productos.show', $producto))->assertOk();
        $this->get(route('inventario.productos.edit', $producto))->assertOk();
        $this->get(route('inventario.almacenes.show', $almacen))->assertOk();

        $cliente = $this->cliente();
        $this->get(route('ventas.clientes.show', $cliente))->assertOk();
        $this->get(route('ventas.clientes.edit', $cliente))->assertOk();

        $proveedor = $this->proveedor();
        $this->get(route('compras.proveedores.show', $proveedor))->assertOk();
        $this->get(route('compras.proveedores.edit', $proveedor))->assertOk();

        $lista = ListaPrecio::create(['codigo' => 'GRAL', 'nombre' => 'General', 'es_predeterminada' => true]);
        $this->get(route('ventas.listas-precios.show', $lista))->assertOk();
    }

    #[Test]
    public function las_fichas_de_los_documentos_de_inventario_abren(): void
    {
        $this->actingAs($this->admin);

        $almacen = $this->almacen();

        $traspaso = Traspaso::create([
            'almacen_origen_id' => $almacen->id,
            'almacen_destino_id' => $this->almacen()->id,
        ]);
        $ajuste = AjusteInventario::create(['motivo' => 'Merma de prueba']);
        $conteo = ConteoInventario::create(['almacen_id' => $almacen->id]);
        $regla = ReglaReorden::create([
            'producto_id' => $this->producto()->id,
            'almacen_id' => $almacen->id,
            'cantidad_minima' => 5,
            'cantidad_maxima' => 50,
        ]);

        $this->get(route('inventario.traspasos.show', $traspaso))->assertOk();
        $this->get(route('inventario.traspasos.edit', $traspaso))->assertOk();
        $this->get(route('inventario.ajustes.show', $ajuste))->assertOk();
        $this->get(route('inventario.ajustes.edit', $ajuste))->assertOk();
        $this->get(route('inventario.conteos.show', $conteo))->assertOk();
        $this->get(route('inventario.conteos.edit', $conteo))->assertOk();
        $this->get(route('inventario.reglas-reorden.edit', $regla))->assertOk();
    }

    #[Test]
    public function las_fichas_de_los_documentos_de_compras_abren(): void
    {
        $this->actingAs($this->admin);

        $proveedor = $this->proveedor();
        $almacen = $this->almacen();

        $requisicion = Requisicion::create(['solicitante_id' => $this->admin->id]);
        $orden = OrdenCompra::create(['proveedor_id' => $proveedor->id, 'fecha' => now()->toDateString()]);
        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);
        $factura = FacturaProveedor::create(['proveedor_id' => $proveedor->id, 'fecha' => now()->toDateString()]);
        $devolucion = DevolucionCompra::create([
            'factura_proveedor_id' => $factura->id,
            'proveedor_id' => $proveedor->id,
            'motivo' => 'defectuoso',
            'fecha' => now()->toDateString(),
        ]);
        $pago = Pago::create([
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->toDateString(),
            'monto' => 100,
        ]);

        $this->get(route('compras.requisiciones.show', $requisicion))->assertOk();
        $this->get(route('compras.requisiciones.edit', $requisicion))->assertOk();
        $this->get(route('compras.ordenes.show', $orden))->assertOk();
        $this->get(route('compras.ordenes.edit', $orden))->assertOk();
        $this->get(route('compras.recepciones.show', $recepcion))->assertOk();
        $this->get(route('compras.facturas.show', $factura))->assertOk();
        $this->get(route('compras.facturas.edit', $factura))->assertOk();
        $this->get(route('compras.devoluciones.show', $devolucion))->assertOk();
        $this->get(route('compras.pagos.show', $pago))->assertOk();
        $this->get(route('compras.pagos.edit', $pago))->assertOk();
    }

    #[Test]
    public function las_fichas_de_los_documentos_de_ventas_abren(): void
    {
        $this->actingAs($this->admin);

        $cliente = $this->cliente();

        $cotizacion = Cotizacion::create(['cliente_id' => $cliente->id, 'fecha' => now()->toDateString()]);
        $pedido = Pedido::create(['cliente_id' => $cliente->id, 'fecha' => now()->toDateString()]);
        $factura = Factura::create(['cliente_id' => $cliente->id, 'fecha_emision' => now()->toDateString()]);
        $nota = NotaCredito::create([
            'factura_id' => $factura->id,
            'cliente_id' => $cliente->id,
            'motivo' => 'error',
            'fecha_emision' => now()->toDateString(),
        ]);
        $cobro = Cobro::create([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'monto' => 100,
        ]);

        $this->get(route('ventas.cotizaciones.show', $cotizacion))->assertOk();
        $this->get(route('ventas.cotizaciones.edit', $cotizacion))->assertOk();
        $this->get(route('ventas.pedidos.show', $pedido))->assertOk();
        $this->get(route('ventas.pedidos.edit', $pedido))->assertOk();
        $this->get(route('ventas.facturas.show', $factura))->assertOk();
        $this->get(route('ventas.facturas.edit', $factura))->assertOk();
        $this->get(route('ventas.notas-credito.show', $nota))->assertOk();
        $this->get(route('ventas.cobros.show', $cobro))->assertOk();
        $this->get(route('ventas.cobros.edit', $cobro))->assertOk();
    }

    #[Test]
    public function los_reportes_se_exportan_a_csv(): void
    {
        $this->actingAs($this->admin);

        $rutas = [
            'inventario.reportes.existencias', 'inventario.reportes.movimientos',
            'inventario.reportes.stock-bajo', 'inventario.reportes.valoracion',
            'inventario.reportes.caducidades',
            'compras.reportes.por-periodo', 'compras.reportes.por-proveedor',
            'compras.reportes.por-producto', 'compras.reportes.pendientes-por-recibir',
            'compras.reportes.cuentas-por-pagar',
            'ventas.reportes.por-periodo', 'ventas.reportes.por-cliente',
            'ventas.reportes.por-producto', 'ventas.reportes.cuentas-por-cobrar',
            'ventas.reportes.devoluciones',
        ];

        foreach ($rutas as $ruta) {
            $respuesta = $this->get(route($ruta, ['formato' => 'csv']));

            $respuesta->assertOk();
            $respuesta->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    #[Test]
    public function el_autocompletado_de_producto_responde_con_precio_y_disponible(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto(['nombre' => 'Guantes de nitrilo', 'precio_venta' => 235]);
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 42);

        $respuesta = $this->getJson(route('api.inventario.productos.buscar', [
            'q' => 'nitrilo',
            'almacen_id' => $almacen->id,
        ]));

        $respuesta->assertOk();

        // Un float sin decimales viaja como entero en JSON, asi que se comparan
        // los valores ya convertidos y no la representacion.
        $primero = $respuesta->json('datos.0');

        $this->assertSame($producto->sku, $primero['sku']);
        $this->assertSame(235.0, (float) $primero['precio_venta']);
        $this->assertSame(42.0, (float) $primero['disponible']);
    }

    #[Test]
    public function el_menu_lateral_muestra_los_tres_modulos(): void
    {
        $this->actingAs($this->admin);

        $respuesta = $this->get(route('inventario.dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('Existencias');
        $respuesta->assertSee('Ordenes de compra');
        $respuesta->assertSee('Notas de credito');
    }

    #[Test]
    public function el_seeder_de_demostracion_deja_el_sistema_navegable(): void
    {
        $this->seed(DatosDemoErpSeeder::class);
        $this->actingAs($this->admin);

        $this->assertDatabaseCount('productos', 8);
        $this->assertDatabaseCount('almacenes', 2);

        // Con datos dentro, las pantallas que agregan y suman son otras: hay que
        // volver a abrirlas.
        $this->get(route('inventario.dashboard'))->assertOk();
        $this->get(route('inventario.existencias.index'))->assertOk();
        $this->get(route('inventario.reportes.stock-bajo'))->assertOk();
        $this->get(route('inventario.reportes.valoracion'))->assertOk();
        $this->get(route('inventario.reportes.caducidades'))->assertOk();
        $this->get(route('ventas.clientes.index'))->assertOk();
        $this->get(route('compras.proveedores.index'))->assertOk();
    }
}
