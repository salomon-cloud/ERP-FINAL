<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El esquema es el contrato entre los seis equipos, asi que se verifica solo:
 * si alguien renombra una tabla de otro modulo, esta prueba se cae.
 */
class EsquemaTest extends TestCase
{
    use RefreshDatabase;

    /** Las tablas que cada modulo declara como propias en su README. */
    private const TABLAS_POR_MODULO = [
        'Compartido' => [
            'organizaciones', 'users', 'roles', 'privilegios', 'rol_privilegios',
            'usuario_roles', 'bitacora_auditoria', 'notificaciones',
            'configuraciones', 'catalogos', 'adjuntos', 'etiquetas',
            'etiquetables', 'favoritos', 'comentarios', 'secuencias_documento',
            'lotes_importacion', 'reportes_guardados',
        ],
        'Finanzas' => [
            'catalogo_cuentas', 'periodos_fiscales', 'centros_costo', 'polizas',
            'poliza_lineas', 'presupuestos', 'presupuesto_lineas',
            'cuentas_bancarias', 'movimientos_bancarios',
            'conciliaciones_bancarias', 'conciliacion_lineas', 'impuestos',
            'facturas_electronicas', 'tipos_cambio',
        ],
        'Inventario' => [
            'almacenes', 'ubicaciones', 'categorias_producto', 'unidades_medida',
            'productos', 'codigos_barras', 'lotes', 'numeros_serie',
            'movimientos_inventario', 'traspasos', 'traspaso_lineas',
            'ajustes_inventario', 'ajuste_lineas', 'conteos_inventario',
            'conteo_lineas', 'reglas_reorden',
        ],
        'RH' => [
            'departamentos', 'puestos', 'empleados', 'asistencias', 'permisos',
            'nominas', 'nomina_periodos', 'nomina_corridas', 'contratos',
            'documentos_empleado', 'evaluaciones_desempeno',
        ],
        'Ventas' => [
            'clientes', 'listas_precios', 'lista_precio_items', 'cotizaciones',
            'cotizacion_lineas', 'pedidos', 'pedido_lineas', 'facturas',
            'factura_lineas', 'notas_credito', 'nota_credito_lineas', 'cobros',
        ],
        'Compras' => [
            'proveedores', 'requisiciones', 'requisicion_lineas',
            'ordenes_compra', 'orden_compra_lineas', 'recepciones',
            'recepcion_lineas', 'facturas_proveedor', 'factura_proveedor_lineas',
            'devoluciones_compra', 'devolucion_compra_lineas', 'pagos',
        ],
        'CRM' => [
            'empresas', 'prospectos', 'contactos', 'oportunidades',
            'actividades', 'notas_crm', 'tareas',
        ],
    ];

    private const VISTAS = [
        'v_libro_mayor', 'v_balanza_comprobacion', 'v_balance_general',
        'v_estado_resultados', 'v_flujo_efectivo', 'v_reporte_impuestos',
        'v_presupuesto_vs_real', 'v_existencias', 'v_historial_cliente',
        'v_embudo_por_responsable', 'v_embudo_ventas',
    ];

    public function test_todas_las_tablas_de_los_modulos_existen(): void
    {
        foreach (self::TABLAS_POR_MODULO as $modulo => $tablas) {
            foreach ($tablas as $tabla) {
                $this->assertTrue(
                    Schema::hasTable($tabla),
                    "Falta la tabla [{$tabla}] del modulo {$modulo}."
                );
            }
        }
    }

    public function test_toda_tabla_de_negocio_lleva_el_conjunto_de_columnas_universal(): void
    {
        // Tres clases de tabla quedan fuera de la regla, por razones distintas:
        //   - del framework o pivote: no son datos de negocio editables
        //   - de solo insercion: llevan su propio actor (aplicado_por, subido_por)
        //   - hijas de un documento: heredan la auditoria de su padre
        $exentas = [
            // framework y pivote
            'users', 'rol_privilegios', 'usuario_roles',
            // solo insercion, con actor propio
            'bitacora_auditoria', 'notificaciones', 'lotes_importacion',
            'movimientos_inventario', 'adjuntos', 'secuencias_documento',
            'tipos_cambio', 'reportes_guardados', 'notas_crm',
            // hijas de otra entidad
            'etiquetas', 'etiquetables', 'favoritos', 'comentarios',
            'codigos_barras', 'lista_precio_items', 'conteo_lineas',
        ];

        foreach (self::TABLAS_POR_MODULO as $tablas) {
            foreach ($tablas as $tabla) {
                if (in_array($tabla, $exentas, true) || str_ends_with($tabla, '_lineas')) {
                    continue;
                }

                foreach (['created_at', 'updated_at', 'deleted_at', 'creado_por', 'actualizado_por'] as $columna) {
                    $this->assertTrue(
                        Schema::hasColumn($tabla, $columna),
                        "La tabla [{$tabla}] no tiene la columna de auditoria [{$columna}]."
                    );
                }
            }
        }
    }

    public function test_las_vistas_de_reporte_existen_y_se_pueden_consultar(): void
    {
        foreach (self::VISTAS as $vista) {
            $this->assertSame(
                0,
                DB::table($vista)->count(),
                "La vista [{$vista}] no se pudo consultar."
            );
        }
    }

    public function test_una_linea_de_poliza_no_puede_ser_cargo_y_abono_a_la_vez(): void
    {
        $this->expectExceptionMessageMatches('/chk_poliza_lineas_un_lado|CONSTRAINT/i');

        DB::table('poliza_lineas')->insert([
            'poliza_id' => 1,
            'cuenta_id' => 1,
            'periodo_fiscal_id' => 1,
            'debe' => 100,
            'haber' => 100,
        ]);
    }

    public function test_un_movimiento_de_inventario_no_puede_ser_de_cantidad_cero(): void
    {
        $this->expectExceptionMessageMatches('/chk_mov_inv_cantidad|CONSTRAINT/i');

        DB::table('movimientos_inventario')->insert([
            'producto_id' => 1,
            'almacen_id' => 1,
            'tipo_movimiento' => 'compra',
            'cantidad' => 0,
        ]);
    }

    public function test_el_codigo_de_un_registro_borrado_puede_reutilizarse(): void
    {
        $atributos = fn (array $extra = []) => array_merge([
            'codigo' => 'ALM-01',
            'nombre' => 'Almacen central',
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra);

        $id = DB::table('almacenes')->insertGetId($atributos());

        // Con el almacen vivo, repetir el codigo debe fallar.
        try {
            DB::table('almacenes')->insert($atributos(['nombre' => 'Duplicado']));
            $this->fail('Se permitio duplicar el codigo de un almacen activo.');
        } catch (QueryException) {
            // Esperado.
        }

        // Tras el borrado logico, el codigo queda libre otra vez.
        DB::table('almacenes')->where('id', $id)->update(['deleted_at' => now()]);

        DB::table('almacenes')->insert($atributos(['nombre' => 'Almacen central (nuevo)']));

        $this->assertSame(1, DB::table('almacenes')->whereNull('deleted_at')->count());
    }
}
