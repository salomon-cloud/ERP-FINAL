<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\ReglaReorden;
use App\Modules\Inventario\Models\UnidadMedida;
use App\Modules\Inventario\Services\ServicioMovimientoInventario;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\ListaPrecio;
use Illuminate\Database\Seeder;

/**
 * Datos de DEMOSTRACION de Ventas, Compras e Inventario.
 *
 * NO es parte de los cimientos y NO debe correr en produccion: aqui hay
 * productos, clientes y proveedores inventados. CimientosSeeder sigue siendo lo
 * unico seguro de ejecutar en un servidor real.
 *
 *     php artisan db:seed --class=DatosDemoErpSeeder
 *
 * Deja el sistema listo para recorrer los dos flujos completos:
 * un catalogo con existencia inicial, dos almacenes, clientes con lista de
 * precios y proveedores. Los documentos (cotizaciones, pedidos, ordenes) NO se
 * siembran a proposito: capturarlos es justo lo que hay que poder probar.
 *
 * El catalogo mezcla familias distintas -- medicamentos, insumos, papeleria --
 * para que se vea que un solo catalogo con categorias basta (docs/david.md D4).
 */
class DatosDemoErpSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = $this->sembrarUnidades();
        $categorias = $this->sembrarCategorias();
        $almacenes = $this->sembrarAlmacenes();
        $productos = $this->sembrarProductos($categorias, $unidades);

        $this->sembrarExistenciaInicial($productos, $almacenes['CEDIS']);
        $this->sembrarReglasDeReorden($productos, $almacenes['CEDIS']);
        $this->sembrarLotes($productos);
        $this->sembrarClientes();
        $this->sembrarProveedores();

        $this->command?->info('Datos de demostracion del ERP sembrados.');
    }

    /** @return array<string, UnidadMedida> */
    private function sembrarUnidades(): array
    {
        $unidades = [
            ['codigo' => 'PZA', 'nombre' => 'Pieza', 'factor_base' => 1, 'es_base' => true],
            ['codigo' => 'CAJA12', 'nombre' => 'Caja con 12 piezas', 'factor_base' => 12, 'es_base' => false],
            ['codigo' => 'KG', 'nombre' => 'Kilogramo', 'factor_base' => 1, 'es_base' => false],
            ['codigo' => 'LT', 'nombre' => 'Litro', 'factor_base' => 1, 'es_base' => false],
        ];

        $mapa = [];

        foreach ($unidades as $unidad) {
            $mapa[$unidad['codigo']] = UnidadMedida::firstOrCreate(['codigo' => $unidad['codigo']], $unidad);
        }

        return $mapa;
    }

    /** @return array<string, CategoriaProducto> */
    private function sembrarCategorias(): array
    {
        $raices = [
            'MED' => 'Medicamentos',
            'INS' => 'Insumos',
            'PAP' => 'Papeleria',
        ];

        $mapa = [];

        foreach ($raices as $codigo => $nombre) {
            $mapa[$codigo] = CategoriaProducto::firstOrCreate(['codigo' => $codigo], ['nombre' => $nombre]);
        }

        // Una subcategoria, para que se vea que filtrar por "Insumos" trae
        // tambien lo que cuelga de el.
        $mapa['INS-CUR'] = CategoriaProducto::firstOrCreate(
            ['codigo' => 'INS-CUR'],
            ['nombre' => 'Curacion', 'padre_id' => $mapa['INS']->id],
        );

        return $mapa;
    }

    /** @return array<string, Almacen> */
    private function sembrarAlmacenes(): array
    {
        $almacenes = [
            'CEDIS' => ['nombre' => 'Centro de distribucion', 'direccion' => 'Parque industrial norte'],
            'SUC01' => ['nombre' => 'Sucursal centro', 'direccion' => 'Av. Juarez 100'],
        ];

        $mapa = [];

        foreach ($almacenes as $codigo => $datos) {
            $almacen = Almacen::firstOrCreate(['codigo' => $codigo], $datos + ['codigo' => $codigo]);

            // Dos ubicaciones por almacen: una surtible y una de cuarentena,
            // que es justo la distincion que hace util `es_surtible`.
            $almacen->ubicaciones()->firstOrCreate(
                ['codigo' => 'A-01'],
                ['nombre' => 'Pasillo A, rack 1', 'es_surtible' => true],
            );
            $almacen->ubicaciones()->firstOrCreate(
                ['codigo' => 'CUAR'],
                ['nombre' => 'Cuarentena', 'es_surtible' => false],
            );

            $mapa[$codigo] = $almacen;
        }

        return $mapa;
    }

    /**
     * @param  array<string, CategoriaProducto>  $categorias
     * @param  array<string, UnidadMedida>  $unidades
     * @return array<string, Producto>
     */
    private function sembrarProductos(array $categorias, array $unidades): array
    {
        $catalogo = [
            ['sku' => 'MED-0001', 'nombre' => 'Paracetamol 500 mg, caja con 20', 'categoria' => 'MED', 'unidad' => 'PZA', 'costo' => 18.50, 'precio' => 32.00],
            ['sku' => 'MED-0002', 'nombre' => 'Ibuprofeno 400 mg, caja con 10', 'categoria' => 'MED', 'unidad' => 'PZA', 'costo' => 24.00, 'precio' => 45.00],
            ['sku' => 'INS-0001', 'nombre' => 'Guantes de nitrilo, caja con 100', 'categoria' => 'INS-CUR', 'unidad' => 'PZA', 'costo' => 145.00, 'precio' => 235.00],
            ['sku' => 'INS-0002', 'nombre' => 'Gasa esteril 10x10, paquete', 'categoria' => 'INS-CUR', 'unidad' => 'PZA', 'costo' => 32.00, 'precio' => 58.00],
            ['sku' => 'INS-0003', 'nombre' => 'Alcohol etilico 70%, litro', 'categoria' => 'INS', 'unidad' => 'LT', 'costo' => 42.00, 'precio' => 75.00],
            ['sku' => 'PAP-0001', 'nombre' => 'Papel bond carta, paquete con 500', 'categoria' => 'PAP', 'unidad' => 'PZA', 'costo' => 88.00, 'precio' => 139.00],
            ['sku' => 'PAP-0002', 'nombre' => 'Boligrafo negro, caja con 12', 'categoria' => 'PAP', 'unidad' => 'CAJA12', 'costo' => 54.00, 'precio' => 96.00],
            ['sku' => 'SRV-0001', 'nombre' => 'Servicio de instalacion', 'categoria' => 'PAP', 'unidad' => 'PZA', 'costo' => 0, 'precio' => 850.00, 'servicio' => true],
        ];

        $mapa = [];

        foreach ($catalogo as $fila) {
            $mapa[$fila['sku']] = Producto::firstOrCreate(['sku' => $fila['sku']], [
                'nombre' => $fila['nombre'],
                'categoria_id' => $categorias[$fila['categoria']]->id,
                'unidad_id' => $unidades[$fila['unidad']]->id,
                'costo' => $fila['costo'],
                'precio_venta' => $fila['precio'],
                'stock_minimo' => ($fila['servicio'] ?? false) ? 0 : 20,
                'stock_maximo' => ($fila['servicio'] ?? false) ? 0 : 200,
                // Un servicio se vende y se factura, pero no descuenta existencia.
                'es_inventariable' => ! ($fila['servicio'] ?? false),
                'estado' => 'activo',
            ]);
        }

        return $mapa;
    }

    /** @param array<string, Producto> $productos */
    private function sembrarExistenciaInicial(array $productos, Almacen $almacen): void
    {
        $movimientos = app(ServicioMovimientoInventario::class);

        // Cantidades desiguales a proposito: asi el reporte de stock bajo tiene
        // algo que mostrar desde el primer minuto.
        $inicial = [
            'MED-0001' => 150, 'MED-0002' => 8, 'INS-0001' => 60,
            'INS-0002' => 12, 'INS-0003' => 90, 'PAP-0001' => 40, 'PAP-0002' => 25,
        ];

        foreach ($inicial as $sku => $cantidad) {
            $producto = $productos[$sku];

            if ($producto->movimientos()->exists()) {
                continue;
            }

            $movimientos->registrar(
                TipoMovimiento::Inicial,
                (int) $producto->id,
                (int) $almacen->id,
                (float) $cantidad,
                (float) $producto->costo,
            );
        }
    }

    /** @param array<string, Producto> $productos */
    private function sembrarReglasDeReorden(array $productos, Almacen $almacen): void
    {
        foreach ($productos as $producto) {
            if (! $producto->es_inventariable) {
                continue;
            }

            ReglaReorden::firstOrCreate(
                ['producto_id' => $producto->id, 'almacen_id' => $almacen->id],
                [
                    'cantidad_minima' => 20,
                    'cantidad_maxima' => 200,
                    'cantidad_reorden' => 0,
                    'dias_entrega' => 5,
                ],
            );
        }
    }

    /** @param array<string, Producto> $productos */
    private function sembrarLotes(array $productos): void
    {
        // Solo los medicamentos: es donde la caducidad importa de verdad.
        $lotes = [
            'MED-0001' => ['L-2026-A', now()->addMonths(8)],
            'MED-0002' => ['L-2026-B', now()->addDays(20)],
        ];

        foreach ($lotes as $sku => [$numero, $caducidad]) {
            Lote::firstOrCreate(
                ['producto_id' => $productos[$sku]->id, 'numero_lote' => $numero],
                ['fecha_caducidad' => $caducidad->toDateString()],
            );
        }
    }

    private function sembrarClientes(): void
    {
        $mayoreo = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAYOREO'],
            ['nombre' => 'Precios de mayoreo', 'moneda' => 'MXN', 'es_predeterminada' => false],
        );

        // Un escalon por volumen, para que se vea como resuelve el precio.
        foreach (Producto::vendibles()->get() as $producto) {
            $mayoreo->items()->firstOrCreate(
                ['producto_id' => $producto->id, 'cantidad_minima' => 50],
                ['precio' => round((float) $producto->precio_venta * 0.85, 2)],
            );
        }

        $net30 = Catalogo::grupo('condiciones_pago')->where('codigo', 'NET30')->first();
        $contado = Catalogo::grupo('condiciones_pago')->where('codigo', 'CONTADO')->first();

        $clientes = [
            ['codigo' => 'CLI-001', 'nombre' => 'Farmacias del Valle', 'rfc' => 'FDV010203AB1',
                'correo' => 'compras@farmaciasdelvalle.mx', 'limite_credito' => 150000,
                'condicion_pago_id' => $net30?->id, 'lista_precio_id' => $mayoreo->id],
            ['codigo' => 'CLI-002', 'nombre' => 'Clinica Santa Rosa', 'rfc' => 'CSR040506CD2',
                'correo' => 'admon@clinicasantarosa.mx', 'limite_credito' => 80000,
                'condicion_pago_id' => $net30?->id],
            ['codigo' => 'CLI-003', 'nombre' => 'Mostrador general', 'rfc' => null,
                'correo' => null, 'limite_credito' => 0, 'condicion_pago_id' => $contado?->id],
        ];

        foreach ($clientes as $cliente) {
            Cliente::firstOrCreate(['codigo' => $cliente['codigo']], $cliente + [
                'moneda' => 'MXN',
                'estado' => 'activo',
            ]);
        }
    }

    private function sembrarProveedores(): void
    {
        $net30 = Catalogo::grupo('condiciones_pago')->where('codigo', 'NET30')->first();

        $proveedores = [
            ['codigo' => 'PRV-001', 'nombre' => 'Distribuidora Farmaceutica Nacional',
                'rfc' => 'DFN070809EF3', 'contacto' => 'Ana Solis', 'correo' => 'ventas@dfn.mx'],
            ['codigo' => 'PRV-002', 'nombre' => 'Insumos Medicos del Norte',
                'rfc' => 'IMN101112GH4', 'contacto' => 'Jorge Lara', 'correo' => 'pedidos@imnorte.mx'],
            ['codigo' => 'PRV-003', 'nombre' => 'Papeleria Corporativa',
                'rfc' => 'PCO131415IJ5', 'contacto' => 'Rosa Pena', 'correo' => 'contacto@papeleriacorp.mx'],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::firstOrCreate(['codigo' => $proveedor['codigo']], $proveedor + [
                'condicion_pago_id' => $net30?->id,
                'moneda' => 'MXN',
                'estado' => 'activo',
            ]);
        }
    }
}
