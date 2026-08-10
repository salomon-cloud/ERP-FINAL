<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Erp;

use App\Modules\Inventario\Enums\EstadoAjuste;
use App\Modules\Inventario\Enums\EstadoConteo;
use App\Modules\Inventario\Enums\EstadoTraspaso;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\AjusteInventario;
use App\Modules\Inventario\Models\ConteoInventario;
use App\Modules\Inventario\Models\MovimientoInventario;
use App\Modules\Inventario\Models\ReglaReorden;
use App\Modules\Inventario\Models\Traspaso;
use App\Modules\Inventario\Services\ServicioAjusteInventario;
use App\Modules\Inventario\Services\ServicioConteo;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use App\Modules\Inventario\Services\ServicioTraspaso;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * El nucleo de Inventario: existencia derivada, traspasos, ajustes y conteos.
 *
 * Si algo de aqui se rompe, Ventas y Compras dejan de ser confiables: los dos
 * apoyan sus numeros en este libro.
 */
class InventarioTest extends PruebaErp
{
    #[Test]
    public function la_existencia_se_deriva_de_los_movimientos(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();

        $this->cargarExistencia($producto, $almacen, 100, 50);

        $existencias = app(ServicioExistencias::class);

        $this->assertSame(100.0, $existencias->fisica($producto->id, $almacen->id));
        $this->assertSame(100.0, $existencias->disponible($producto->id, $almacen->id));
        $this->assertSame(0.0, $existencias->apartado($producto->id, $almacen->id));
    }

    #[Test]
    public function una_salida_mayor_que_la_existencia_se_rechaza(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();

        $this->cargarExistencia($producto, $almacen, 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Existencia insuficiente');

        app(ServicioMovimientoInventario::class)->registrar(
            TipoMovimiento::Venta, $producto->id, $almacen->id, 10,
        );
    }

    #[Test]
    public function un_apartado_baja_el_disponible_pero_no_la_existencia_fisica(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 20);

        app(ServicioMovimientoInventario::class)->registrar(
            TipoMovimiento::Apartado, $producto->id, $almacen->id, 8,
        );

        $existencias = app(ServicioExistencias::class);

        $this->assertSame(20.0, $existencias->fisica($producto->id, $almacen->id));
        $this->assertSame(8.0, $existencias->apartado($producto->id, $almacen->id));
        $this->assertSame(12.0, $existencias->disponible($producto->id, $almacen->id));
    }

    #[Test]
    public function el_apartado_no_altera_el_valor_del_inventario(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 10, 30);

        app(ServicioMovimientoInventario::class)->registrar(
            TipoMovimiento::Apartado, $producto->id, $almacen->id, 4, 999,
        );

        $fila = app(ServicioExistencias::class)->consulta(['producto_id' => $producto->id])->first();

        // 10 piezas x 30 = 300. El apartado se graba con costo cero a proposito.
        $this->assertSame(300.0, (float) $fila->valor_inventario);
    }

    #[Test]
    public function revertir_un_movimiento_genera_el_contrario_y_conserva_el_original(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();

        $movimiento = app(ServicioMovimientoInventario::class)->registrar(
            TipoMovimiento::Compra, $producto->id, $almacen->id, 15, 40,
        );

        app(ServicioMovimientoInventario::class)->revertir($movimiento);

        $this->assertSame(0.0, $this->disponible($producto, $almacen));
        $this->assertSame(2, MovimientoInventario::where('producto_id', $producto->id)->count());
        $this->assertDatabaseHas('movimientos_inventario', [
            'id' => $movimiento->id,
            'estado' => 'aplicado',
        ]);
    }

    #[Test]
    public function un_traspaso_mueve_la_existencia_de_un_almacen_a_otro(): void
    {
        $producto = $this->producto();
        $origen = $this->almacen(['codigo' => 'ORI']);
        $destino = $this->almacen(['codigo' => 'DES']);

        $this->cargarExistencia($producto, $origen, 50, 20);

        $traspaso = Traspaso::create([
            'almacen_origen_id' => $origen->id,
            'almacen_destino_id' => $destino->id,
        ]);
        $traspaso->lineas()->create(['producto_id' => $producto->id, 'cantidad' => 20, 'costo_unitario' => 20]);

        $servicio = app(ServicioTraspaso::class);

        $servicio->enviar($traspaso, $this->admin);

        // En transito: ya salio del origen y todavia no entra al destino.
        $this->assertSame(30.0, $this->disponible($producto, $origen));
        $this->assertSame(0.0, $this->disponible($producto, $destino));
        $this->assertSame(EstadoTraspaso::EnTransito, $traspaso->refresh()->estado);

        $servicio->recibir($traspaso, $this->admin);

        $this->assertSame(30.0, $this->disponible($producto, $origen));
        $this->assertSame(20.0, $this->disponible($producto, $destino));
        $this->assertSame(EstadoTraspaso::Recibido, $traspaso->refresh()->estado);
    }

    #[Test]
    public function un_traspaso_en_transito_ya_no_se_cancela(): void
    {
        $producto = $this->producto();
        $origen = $this->almacen();
        $destino = $this->almacen();
        $this->cargarExistencia($producto, $origen, 10);

        $traspaso = Traspaso::create([
            'almacen_origen_id' => $origen->id,
            'almacen_destino_id' => $destino->id,
        ]);
        $traspaso->lineas()->create(['producto_id' => $producto->id, 'cantidad' => 5, 'costo_unitario' => 10]);

        app(ServicioTraspaso::class)->enviar($traspaso, $this->admin);

        $this->expectException(RuntimeException::class);

        app(ServicioTraspaso::class)->cancelar($traspaso->refresh());
    }

    #[Test]
    public function un_ajuste_aplicado_mueve_el_inventario_con_su_signo(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 100, 10);

        $ajuste = AjusteInventario::create(['motivo' => 'Merma por rotura en el almacen']);
        $ajuste->lineas()->create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'diferencia' => -7,
            'costo_unitario' => 10,
        ]);

        app(ServicioAjusteInventario::class)->aplicar($ajuste, $this->admin);

        $this->assertSame(93.0, $this->disponible($producto, $almacen));
        $this->assertSame(EstadoAjuste::Aplicado, $ajuste->refresh()->estado);
        $this->assertSame($this->admin->id, $ajuste->aprobado_por);
    }

    #[Test]
    public function un_conteo_convierte_la_diferencia_en_movimientos(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 100, 10);

        $conteo = ConteoInventario::create(['almacen_id' => $almacen->id]);

        $servicio = app(ServicioConteo::class);
        $servicio->iniciar($conteo, $this->admin);

        $linea = $conteo->refresh()->lineas->first();
        $this->assertSame(100.0, (float) $linea->cantidad_esperada);

        // Se contaron 98: faltan dos.
        $servicio->capturar($conteo, [$linea->id => 98.0]);
        $this->assertSame(-2.0, (float) $conteo->refresh()->lineas->first()->diferencia);

        $servicio->cerrar($conteo, $this->admin);

        $this->assertSame(98.0, $this->disponible($producto, $almacen));
        $this->assertSame(EstadoConteo::Cerrado, $conteo->refresh()->estado);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo_movimiento' => TipoMovimiento::Conteo->value,
            'cantidad' => '-2.000000',
        ]);
    }

    #[Test]
    public function el_reporte_de_stock_bajo_lista_lo_que_esta_por_debajo_del_minimo(): void
    {
        $bajo = $this->producto(['nombre' => 'Producto escaso']);
        $suficiente = $this->producto(['nombre' => 'Producto de sobra']);
        $almacen = $this->almacen();

        $this->cargarExistencia($bajo, $almacen, 3);
        $this->cargarExistencia($suficiente, $almacen, 300);

        foreach ([$bajo, $suficiente] as $producto) {
            ReglaReorden::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacen->id,
                'cantidad_minima' => 10,
                'cantidad_maxima' => 100,
            ]);
        }

        $faltantes = app(ServicioExistencias::class)->bajoMinimo();

        $this->assertCount(1, $faltantes);
        $this->assertSame('Producto escaso', $faltantes->first()->producto_nombre);
    }

    #[Test]
    public function el_comando_de_reorden_avisa_de_lo_que_falta(): void
    {
        $producto = $this->producto();
        $almacen = $this->almacen();
        $this->cargarExistencia($producto, $almacen, 1);

        ReglaReorden::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_minima' => 50,
            'cantidad_maxima' => 200,
        ]);

        // Devuelve FAILURE a proposito: asi un cron detecta que hubo faltantes.
        $this->artisan('inventario:reorden')->assertExitCode(1);

        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $this->admin->id,
            'tipo' => 'stock_bajo',
        ]);
    }

    #[Test]
    public function los_folios_de_los_documentos_los_pone_el_observer(): void
    {
        $almacen = $this->almacen();

        $ajuste = AjusteInventario::create(['motivo' => 'Prueba de folio']);
        $conteo = ConteoInventario::create(['almacen_id' => $almacen->id]);
        $traspaso = Traspaso::create([
            'almacen_origen_id' => $almacen->id,
            'almacen_destino_id' => $this->almacen()->id,
        ]);

        $this->assertSame('AJU-000001', $ajuste->numero_ajuste);
        $this->assertSame('CON-000001', $conteo->numero_conteo);
        $this->assertSame('TRA-000001', $traspaso->numero_traspaso);
    }
}
