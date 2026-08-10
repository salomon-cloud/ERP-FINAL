<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Enums\EstadoRequisicion;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Pago;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\Compras\Models\Requisicion;
use App\Modules\Compras\Services\ServicioFacturaProveedor;
use App\Modules\Compras\Services\ServicioOrdenCompra;
use App\Modules\Compras\Services\ServicioPago;
use App\Modules\Compras\Services\ServicioRecepcion;
use App\Modules\Compras\Services\ServicioRequisicion;
use App\Modules\Inventario\Enums\TipoMovimiento;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * La cadena de Compras: requisicion -> orden -> recepcion -> factura -> pago.
 *
 * El caso feliz completo va en una sola prueba a proposito: lo que importa
 * verificar es que los cinco documentos se encadenan, no cada uno por separado.
 */
class ComprasTest extends PruebaErp
{
    #[Test]
    public function la_cadena_completa_de_compra_llega_hasta_el_inventario_y_el_pago(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto(['costo' => 100]);
        $almacen = $this->almacen();
        $proveedor = $this->proveedor();

        // 1. Requisicion
        $requisicion = Requisicion::create(['solicitante_id' => $this->admin->id]);
        $requisicion->lineas()->create(['producto_id' => $producto->id, 'cantidad_solicitada' => 10]);

        $servicioRequisicion = app(ServicioRequisicion::class);
        $servicioRequisicion->enviar($requisicion);
        $servicioRequisicion->revisar($requisicion->refresh(), $this->admin, true);

        $this->assertSame(EstadoRequisicion::Aprobada, $requisicion->refresh()->estado);

        // 2. Orden de compra, copiada de la requisicion
        $orden = $servicioRequisicion->convertirEnOrden($requisicion, $proveedor);

        $this->assertSame(EstadoRequisicion::Convertida, $requisicion->refresh()->estado);
        $this->assertCount(1, $orden->lineas);
        $this->assertSame(1000.0, (float) $orden->total);

        app(ServicioOrdenCompra::class)->confirmar($orden, $orden->version_fila);
        $this->assertSame(EstadoOrdenCompra::Confirmada, $orden->refresh()->estado);

        // 3. Recepcion: aqui nace el inventario
        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);

        $servicioRecepcion = app(ServicioRecepcion::class);
        $servicioRecepcion->agregarLinea($recepcion, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'cantidad_recibida' => 10,
        ]);
        $servicioRecepcion->aplicar($recepcion, $this->admin);

        $this->assertSame(10.0, $this->disponible($producto, $almacen));
        $this->assertSame(EstadoOrdenCompra::Recibida, $orden->refresh()->estado);
        // La requisicion se cierra sola cuando su orden llega completa.
        $this->assertSame(EstadoRequisicion::Cerrada, $requisicion->refresh()->estado);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo_movimiento' => TipoMovimiento::Compra->value,
            'origen_tipo' => 'recepciones',
        ]);

        // 4. Factura, con cotejo de tres vias
        $factura = FacturaProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_compra_id' => $orden->id,
            'fecha' => now()->toDateString(),
        ]);

        $servicioFactura = app(ServicioFacturaProveedor::class);
        $servicioFactura->copiarDesdeOrden($factura);

        $this->assertSame([], $servicioFactura->cotejarTresVias($factura->refresh()));

        $servicioFactura->contabilizar($factura, $factura->version_fila);
        $this->assertSame(EstadoFacturaProveedor::Contabilizada, $factura->refresh()->estado);
        $this->assertSame(EstadoOrdenCompra::Facturada, $orden->refresh()->estado);

        // 5. Pago, en dos partes
        $servicioPago = app(ServicioPago::class);

        $primero = Pago::create([
            'proveedor_id' => $proveedor->id,
            'factura_proveedor_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 400,
        ]);
        $servicioPago->aplicar($primero);

        $this->assertSame(EstadoFacturaProveedor::PagadaParcial, $factura->refresh()->estado);
        $this->assertSame(600.0, $factura->saldo);

        $segundo = Pago::create([
            'proveedor_id' => $proveedor->id,
            'factura_proveedor_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 600,
        ]);
        $servicioPago->aplicar($segundo);

        $this->assertSame(EstadoFacturaProveedor::Pagada, $factura->refresh()->estado);
        $this->assertSame(0.0, $factura->saldo);
    }

    #[Test]
    public function no_se_puede_recibir_mas_de_lo_pedido(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $orden = $this->ordenConfirmada($producto, cantidad: 10);

        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se puede recibir mas de lo pedido');

        app(ServicioRecepcion::class)->agregarLinea($recepcion, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'cantidad_recibida' => 11,
        ]);
    }

    #[Test]
    public function dos_recepciones_parciales_completan_la_orden(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $orden = $this->ordenConfirmada($producto, cantidad: 100);
        $ordenLinea = $orden->lineas->first();

        $servicio = app(ServicioRecepcion::class);

        foreach ([60, 40] as $cantidad) {
            $recepcion = Recepcion::create([
                'orden_compra_id' => $orden->id,
                'almacen_id' => $almacen->id,
                'fecha' => now()->toDateString(),
            ]);

            $servicio->agregarLinea($recepcion, [
                'orden_compra_linea_id' => $ordenLinea->id,
                'cantidad_recibida' => $cantidad,
            ]);
            $servicio->aplicar($recepcion, $this->admin);

            if ($cantidad === 60) {
                $this->assertSame(EstadoOrdenCompra::RecibidaParcial, $orden->refresh()->estado);
            }
        }

        $this->assertSame(EstadoOrdenCompra::Recibida, $orden->refresh()->estado);
        $this->assertSame(100.0, $this->disponible($producto, $almacen));
    }

    #[Test]
    public function cancelar_una_recepcion_aplicada_revierte_su_movimiento(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $orden = $this->ordenConfirmada($producto, cantidad: 20);

        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);

        $servicio = app(ServicioRecepcion::class);
        $servicio->agregarLinea($recepcion, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'cantidad_recibida' => 20,
        ]);
        $servicio->aplicar($recepcion, $this->admin);

        $this->assertSame(20.0, $this->disponible($producto, $almacen));

        $servicio->cancelar($recepcion->refresh());

        $this->assertSame(0.0, $this->disponible($producto, $almacen));
        $this->assertSame(0.0, (float) $orden->lineas->first()->refresh()->cantidad_recibida);
        // Los dos movimientos siguen ahi: el original y su contrario.
        $this->assertSame(2, $producto->movimientos()->count());
    }

    #[Test]
    public function el_cotejo_de_tres_vias_detiene_una_factura_por_mas_de_lo_recibido(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $proveedor = $this->proveedor();
        $orden = $this->ordenConfirmada($producto, cantidad: 100, proveedor: $proveedor);

        // Solo llegaron 60.
        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);
        $servicioRecepcion = app(ServicioRecepcion::class);
        $servicioRecepcion->agregarLinea($recepcion, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'cantidad_recibida' => 60,
        ]);
        $servicioRecepcion->aplicar($recepcion, $this->admin);

        // Pero el proveedor factura 100.
        $factura = FacturaProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_compra_id' => $orden->id,
            'fecha' => now()->toDateString(),
        ]);

        $servicio = app(ServicioFacturaProveedor::class);
        $servicio->agregarLinea($factura, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'producto_id' => $producto->id,
            'cantidad' => 100,
            'costo_unitario' => 100,
        ]);

        $this->assertNotEmpty($servicio->cotejarTresVias($factura->refresh()));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cotejo de tres vias no cuadra');

        $servicio->contabilizar($factura, $factura->version_fila);
    }

    #[Test]
    public function una_orden_con_mercancia_recibida_ya_no_se_cancela(): void
    {
        $this->actingAs($this->admin);

        $producto = $this->producto();
        $almacen = $this->almacen();
        $orden = $this->ordenConfirmada($producto, cantidad: 10);

        $recepcion = Recepcion::create([
            'orden_compra_id' => $orden->id,
            'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(),
        ]);
        $servicio = app(ServicioRecepcion::class);
        $servicio->agregarLinea($recepcion, [
            'orden_compra_linea_id' => $orden->lineas->first()->id,
            'cantidad_recibida' => 5,
        ]);
        $servicio->aplicar($recepcion, $this->admin);

        // Recibir movio la orden a "recibida parcial", que ya no es un estado
        // cancelable: la mercancia esta en el almacen.
        $this->assertSame(EstadoOrdenCompra::RecibidaParcial, $orden->refresh()->estado);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ya no se puede cancelar');

        app(ServicioOrdenCompra::class)->cancelar($orden);
    }

    #[Test]
    public function confirmar_con_una_version_vieja_falla(): void
    {
        $orden = $this->ordenBorrador($this->producto());

        // Alguien mas la confirmo primero, y la version subio.
        app(ServicioOrdenCompra::class)->confirmar($orden, $orden->version_fila);

        $this->expectException(RuntimeException::class);

        app(ServicioOrdenCompra::class)->confirmar($this->ordenBorrador($this->producto()), 99);
    }

    #[Test]
    public function un_pago_mayor_que_el_saldo_se_rechaza(): void
    {
        $this->actingAs($this->admin);

        $proveedor = $this->proveedor();
        $factura = FacturaProveedor::create([
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->toDateString(),
        ]);
        $factura->lineas()->create([
            'producto_id' => $this->producto()->id,
            'cantidad' => 1,
            'costo_unitario' => 500,
            'subtotal' => 500,
            'total' => 500,
        ]);
        $factura->recalcularTotales();
        app(ServicioFacturaProveedor::class)->contabilizar($factura->refresh(), $factura->version_fila);

        $pago = Pago::create([
            'proveedor_id' => $proveedor->id,
            'factura_proveedor_id' => $factura->id,
            'fecha' => now()->toDateString(),
            'monto' => 900,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('excede el saldo');

        app(ServicioPago::class)->aplicar($pago);
    }

    /** Una orden en borrador con una linea, lista para confirmar. */
    private function ordenBorrador($producto, float $cantidad = 10, ?object $proveedor = null): OrdenCompra
    {
        $orden = OrdenCompra::create([
            'proveedor_id' => ($proveedor ?? $this->proveedor())->id,
            'fecha' => now()->toDateString(),
        ]);

        app(ServicioOrdenCompra::class)->agregarLinea($orden, [
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'costo_unitario' => 100,
        ]);

        return $orden->refresh();
    }

    private function ordenConfirmada($producto, float $cantidad = 10, ?object $proveedor = null): OrdenCompra
    {
        $orden = $this->ordenBorrador($producto, $cantidad, $proveedor);

        app(ServicioOrdenCompra::class)->confirmar($orden, $orden->version_fila);

        return $orden->refresh()->load('lineas');
    }
}
