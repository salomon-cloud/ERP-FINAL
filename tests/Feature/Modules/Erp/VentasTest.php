<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Ventas\Enums\EstadoCotizacion;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Enums\EstadoPedido;
use App\Modules\Ventas\Models\Cobro;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Models\NotaCredito;
use App\Modules\Ventas\Models\Pedido;
use App\Modules\Ventas\Services\ServicioCobro;
use App\Modules\Ventas\Services\ServicioCotizacion;
use App\Modules\Ventas\Services\ServicioFactura;
use App\Modules\Ventas\Services\ServicioNotaCredito;
use App\Modules\Ventas\Services\ServicioPedido;
use App\Modules\Ventas\Services\ServicioResolverPrecio;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * La cadena de Ventas: cotizacion -> pedido -> surtido -> factura -> cobro,
 * y su vuelta atras con notas de credito.
 *
 * Lo que de verdad se esta probando es que Ventas e Inventario son UN sistema:
 * confirmar aparta, surtir descuenta, y una devolucion regresa.
 */
class VentasTest extends PruebaErp
{
    #[Test]
    public function la_cadena_completa_de_venta_descuenta_inventario_y_cobra(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto(['precio_venta' => 200, 'costo' => 100]);
        $almacen = $this->almacen();
        $cliente = $this->cliente();

        $this->cargarExistencia($producto, $almacen, 50, 100);

        // 1. Cotizacion
        $cotizacion = Cotizacion::create([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'vigencia' => now()->addDays(15)->toDateString(),
        ]);

        $servicioCotizacion = app(ServicioCotizacion::class);
        $servicioCotizacion->agregarLinea($cotizacion, ['producto_id' => $producto->id, 'cantidad' => 10]);

        $this->assertSame(2000.0, (float) $cotizacion->refresh()->total);

        $servicioCotizacion->enviar($cotizacion);
        $servicioCotizacion->responder($cotizacion->refresh(), true);
        $this->assertSame(EstadoCotizacion::Aceptada, $cotizacion->refresh()->estado);

        // 2. Pedido
        $pedido = $servicioCotizacion->convertirEnPedido($cotizacion, $almacen->id);

        $this->assertSame(EstadoCotizacion::Convertida, $cotizacion->refresh()->estado);
        $this->assertSame(2000.0, (float) $pedido->total);

        // 3. Confirmar: aparta, no saca
        app(ServicioPedido::class)->confirmar($pedido, $pedido->version_fila);

        $existencias = app(ServicioExistencias::class);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->refresh()->estado);
        $this->assertSame(50.0, $existencias->fisica($producto->id, $almacen->id));
        $this->assertSame(10.0, $existencias->apartado($producto->id, $almacen->id));
        $this->assertSame(40.0, $existencias->disponible($producto->id, $almacen->id));

        // 4. Surtir: libera el apartado y saca la mercancia
        app(ServicioPedido::class)->surtir($pedido->refresh());

        $this->assertSame(EstadoPedido::Surtido, $pedido->refresh()->estado);
        $this->assertSame(40.0, $existencias->fisica($producto->id, $almacen->id));
        $this->assertSame(0.0, $existencias->apartado($producto->id, $almacen->id));
        $this->assertSame(40.0, $existencias->disponible($producto->id, $almacen->id));
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo_movimiento' => TipoMovimiento::Venta->value,
            'origen_tipo' => 'pedidos',
        ]);

        // 5. Factura
        $servicioFactura = app(ServicioFactura::class);
        $factura = $servicioFactura->crearDesdePedido($pedido->refresh());

        $this->assertSame(2000.0, (float) $factura->total);

        $servicioFactura->emitir($factura, $factura->version_fila);
        $this->assertSame(EstadoFactura::Emitida, $factura->refresh()->estado);
        $this->assertSame(EstadoPedido::Facturado, $pedido->refresh()->estado);

        // 6. Cobro en dos partes
        $servicioCobro = app(ServicioCobro::class);

        $servicioCobro->aplicar(Cobro::create([
            'cliente_id' => $cliente->id,
            'factura_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 800,
        ]));

        $this->assertSame(EstadoFactura::CobradaParcial, $factura->refresh()->estado);
        $this->assertSame(1200.0, $factura->saldo);

        $servicioCobro->aplicar(Cobro::create([
            'cliente_id' => $cliente->id,
            'factura_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 1200,
        ]));

        $this->assertSame(EstadoFactura::Cobrada, $factura->refresh()->estado);
        $this->assertSame(0.0, $factura->saldo);
    }

    #[Test]
    public function confirmar_sin_existencia_suficiente_falla_y_no_aparta_nada(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 5);

        $pedido = $this->pedidoConLinea($producto, $almacen, cantidad: 10);

        try {
            app(ServicioPedido::class)->confirmar($pedido, $pedido->version_fila);
            $this->fail('Confirmar deberia haber fallado por falta de existencia.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Existencia insuficiente', $error->getMessage());
        }

        // La transaccion se deshizo entera: ni el estado ni el apartado quedaron.
        $this->assertSame(EstadoPedido::Borrador, $pedido->refresh()->estado);
        $this->assertSame(0.0, app(ServicioExistencias::class)->apartado($producto->id, $almacen->id));
        $this->assertSame(5.0, $this->disponible($producto, $almacen));
    }

    #[Test]
    public function dos_pedidos_no_pueden_apartar_la_misma_existencia(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 10);

        $primero = $this->pedidoConLinea($producto, $almacen, cantidad: 8);
        $segundo = $this->pedidoConLinea($producto, $almacen, cantidad: 8);

        app(ServicioPedido::class)->confirmar($primero, $primero->version_fila);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Existencia insuficiente');

        app(ServicioPedido::class)->confirmar($segundo, $segundo->version_fila);
    }

    #[Test]
    public function cancelar_un_pedido_confirmado_libera_su_apartado(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 20);

        $pedido = $this->pedidoConLinea($producto, $almacen, cantidad: 12);
        app(ServicioPedido::class)->confirmar($pedido, $pedido->version_fila);

        $this->assertSame(8.0, $this->disponible($producto, $almacen));

        app(ServicioPedido::class)->cancelar($pedido->refresh());

        $this->assertSame(EstadoPedido::Cancelado, $pedido->refresh()->estado);
        $this->assertSame(20.0, $this->disponible($producto, $almacen));
        // La existencia fisica nunca se movio: apartar no saca nada del anaquel.
        $this->assertSame(20.0, app(ServicioExistencias::class)->fisica($producto->id, $almacen->id));
    }

    #[Test]
    public function un_pedido_surtido_ya_no_se_cancela(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 10);

        $pedido = $this->pedidoConLinea($producto, $almacen, cantidad: 10);
        $servicio = app(ServicioPedido::class);
        $servicio->confirmar($pedido, $pedido->version_fila);
        $servicio->surtir($pedido->refresh());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nota de credito');

        $servicio->cancelar($pedido->refresh());
    }

    #[Test]
    public function un_surtido_parcial_deja_el_resto_apartado(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 30);

        $pedido = $this->pedidoConLinea($producto, $almacen, cantidad: 20);
        $servicio = app(ServicioPedido::class);
        $servicio->confirmar($pedido, $pedido->version_fila);

        $linea = $pedido->refresh()->lineas->first();
        $servicio->surtir($pedido, [$linea->id => 12.0]);

        $existencias = app(ServicioExistencias::class);

        // Salieron 12; siguen apartadas las 8 que faltan.
        $this->assertSame(18.0, $existencias->fisica($producto->id, $almacen->id));
        $this->assertSame(8.0, $existencias->apartado($producto->id, $almacen->id));
        $this->assertSame(10.0, $existencias->disponible($producto->id, $almacen->id));
        $this->assertSame(EstadoPedido::Confirmado, $pedido->refresh()->estado);
    }

    #[Test]
    public function una_nota_de_credito_por_devolucion_regresa_la_mercancia(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto(['precio_venta' => 100]);
        $almacen = $this->almacen();
        $cliente = $this->cliente();
        $this->cargarExistencia($producto, $almacen, 20, 60);

        $factura = $this->facturaEmitida($producto, $almacen, $cliente, cantidad: 10);

        $this->assertSame(10.0, $this->disponible($producto, $almacen));

        $nota = NotaCredito::create([
            'factura_id' => $factura->id,
            'cliente_id' => $cliente->id,
            'motivo' => 'devolucion',
            'fecha_emision' => now()->toDateString(),
        ]);

        $servicio = app(ServicioNotaCredito::class);
        $servicio->agregarLinea($nota, [
            'factura_linea_id' => $factura->lineas->first()->id,
            'cantidad' => 4,
        ]);
        $servicio->emitir($nota->refresh(), $almacen->id);

        $this->assertSame(14.0, $this->disponible($producto, $almacen));
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo_movimiento' => TipoMovimiento::DevolucionEntrada->value,
            'origen_tipo' => 'notas_credito',
        ]);
    }

    #[Test]
    public function una_nota_de_credito_por_descuento_no_toca_el_inventario(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto(['precio_venta' => 100]);
        $almacen = $this->almacen();
        $cliente = $this->cliente();
        $this->cargarExistencia($producto, $almacen, 20, 60);

        $factura = $this->facturaEmitida($producto, $almacen, $cliente, cantidad: 10);
        $disponibleAntes = $this->disponible($producto, $almacen);

        $nota = NotaCredito::create([
            'factura_id' => $factura->id,
            'cliente_id' => $cliente->id,
            'motivo' => 'descuento',
            'fecha_emision' => now()->toDateString(),
        ]);

        $servicio = app(ServicioNotaCredito::class);
        $servicio->agregarLinea($nota, [
            'factura_linea_id' => $factura->lineas->first()->id,
            'cantidad' => 3,
        ]);
        $servicio->emitir($nota->refresh());

        $this->assertSame($disponibleAntes, $this->disponible($producto, $almacen));
    }

    #[Test]
    public function no_se_puede_acreditar_mas_de_lo_facturado(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $cliente = $this->cliente();
        $this->cargarExistencia($producto, $almacen, 20);

        $factura = $this->facturaEmitida($producto, $almacen, $cliente, cantidad: 5);

        $nota = NotaCredito::create([
            'factura_id' => $factura->id,
            'cliente_id' => $cliente->id,
            'motivo' => 'error',
            'fecha_emision' => now()->toDateString(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se puede acreditar mas de lo facturado');

        app(ServicioNotaCredito::class)->agregarLinea($nota, [
            'factura_linea_id' => $factura->lineas->first()->id,
            'cantidad' => 6,
        ]);
    }

    #[Test]
    public function el_precio_sale_de_la_lista_del_cliente_y_respeta_el_volumen(): void
    {
        $producto = $this->producto(['precio_venta' => 100]);

        $lista = ListaPrecio::create(['codigo' => 'MAYOREO', 'nombre' => 'Mayoreo']);
        $lista->items()->create(['producto_id' => $producto->id, 'cantidad_minima' => 1, 'precio' => 90]);
        $lista->items()->create(['producto_id' => $producto->id, 'cantidad_minima' => 50, 'precio' => 75]);

        $cliente = $this->cliente(['lista_precio_id' => $lista->id]);
        $sinLista = $this->cliente();

        $precios = app(ServicioResolverPrecio::class);

        // Con lista: cada escalon en su rango.
        $this->assertSame(90.0, $precios->resolver($producto, $cliente, 10));
        $this->assertSame(75.0, $precios->resolver($producto, $cliente, 50));
        $this->assertSame(75.0, $precios->resolver($producto, $cliente, 200));

        // Sin lista ni predeterminada: el precio del catalogo.
        $this->assertSame(100.0, $precios->resolver($producto, $sinLista, 10));
    }

    #[Test]
    public function una_factura_emitida_no_se_cancela_con_cobros_aplicados(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $cliente = $this->cliente();
        $this->cargarExistencia($producto, $almacen, 20);

        $factura = $this->facturaEmitida($producto, $almacen, $cliente, cantidad: 5);

        app(ServicioCobro::class)->aplicar(Cobro::create([
            'cliente_id' => $cliente->id,
            'factura_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 100,
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tiene cobros aplicados');

        app(ServicioFactura::class)->cancelar($factura->refresh());
    }

    #[Test]
    public function una_cotizacion_vencida_ya_no_se_acepta(): void
    {
        $producto = $this->producto();
        $cliente = $this->cliente();

        $cotizacion = Cotizacion::create([
            'cliente_id' => $cliente->id,
            'fecha' => now()->subDays(30)->toDateString(),
            'vigencia' => now()->subDay()->toDateString(),
        ]);

        $servicio = app(ServicioCotizacion::class);
        $servicio->agregarLinea($cotizacion, ['producto_id' => $producto->id, 'cantidad' => 1]);
        $servicio->enviar($cotizacion);

        $this->assertTrue($cotizacion->refresh()->esta_vencida);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('vencio el');

        $servicio->responder($cotizacion, true);
    }

    /** Un pedido en borrador con una linea lista para confirmar. */
    private function pedidoConLinea($producto, $almacen, float $cantidad): Pedido
    {
        $pedido = Pedido::create([
            'cliente_id' => $this->cliente()->id,
            'fecha' => now()->toDateString(),
        ]);

        app(ServicioPedido::class)->agregarLinea($pedido, [
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad' => $cantidad,
        ]);

        return $pedido->refresh();
    }

    /** El camino corto hasta una factura emitida, para las pruebas que empiezan ahi. */
    private function facturaEmitida($producto, $almacen, $cliente, float $cantidad)
    {
        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
        ]);

        $servicioPedido = app(ServicioPedido::class);
        $servicioPedido->agregarLinea($pedido, [
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad' => $cantidad,
        ]);

        $pedido->refresh();
        $servicioPedido->confirmar($pedido, $pedido->version_fila);
        $servicioPedido->surtir($pedido->refresh());

        $servicioFactura = app(ServicioFactura::class);
        $factura = $servicioFactura->crearDesdePedido($pedido->refresh());
        $servicioFactura->emitir($factura, $factura->version_fila);

        return $factura->refresh()->load('lineas');
    }
}
