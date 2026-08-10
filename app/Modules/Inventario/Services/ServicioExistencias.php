<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoMovimiento;
use App\Modules\Inventario\Enums\TipoMovimiento;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La existencia, derivada del libro de movimientos. No hay otra fuente.
 *
 * TRES CIFRAS Y NO UNA, porque en un almacen de verdad significan cosas
 * distintas:
 *
 *   existencia  lo que hay fisicamente en el anaquel
 *   apartado    lo que ya esta comprometido con un pedido confirmado
 *   disponible  existencia - apartado: lo unico que se puede prometer
 *
 * COMO SE RELACIONA ESTO CON v_existencias
 *
 * La vista `v_existencias` suma TODOS los movimientos aplicados, y los
 * apartados viajan en el mismo libro con signo negativo. Es decir que su
 * columna `existencia` ya es la existencia DISPONIBLE. No es un descuido de la
 * vista: es la razon por la que los apartados se graban con costo_unitario = 0,
 * de modo que `valor_inventario` siga valorizando solo la existencia fisica.
 * La vista es un contrato que no se toca (docs/david.md D2); este servicio
 * agrega el desglose que la vista no da.
 *
 * LA INVARIANTE que protege todo el modulo: la suma del libro por
 * (producto, almacen) nunca puede quedar negativa. Con eso, ni se vende sin
 * existencia ni se aparta dos veces la misma pieza.
 */
class ServicioExistencias
{
    /** Los tipos que son reserva y no existencia fisica, listos para el SQL. */
    private const TIPOS_APARTADO = [
        TipoMovimiento::Apartado->value,
        TipoMovimiento::LiberacionApartado->value,
    ];

    /**
     * La consulta base de existencias, agrupada por producto y almacen.
     *
     * Se arma con el Query Builder y no con Eloquent porque es una agregacion
     * pura: traer los modelos para sumarlos seria pedirle a PHP lo que la base
     * hace con un indice.
     *
     * @param  array<string, mixed>  $filtros  producto_id, almacen_id, categoria_id, buscar, solo_con_existencia
     */
    public function consulta(array $filtros = [], bool $porUbicacion = false): Builder
    {
        $apartados = implode(',', array_map(fn (string $t) => "'".$t."'", self::TIPOS_APARTADO));

        $agrupar = ['p.id', 'p.sku', 'p.nombre', 'p.categoria_id', 'p.costo', 'p.unidad_id',
            'a.id', 'a.codigo', 'a.nombre'];

        $consulta = DB::table('movimientos_inventario as m')
            ->join('productos as p', 'p.id', '=', 'm.producto_id')
            ->join('almacenes as a', 'a.id', '=', 'm.almacen_id')
            ->selectRaw('p.id as producto_id, p.sku, p.nombre as producto_nombre, p.categoria_id, p.costo')
            ->selectRaw('a.id as almacen_id, a.codigo as almacen_codigo, a.nombre as almacen_nombre')
            ->selectRaw("SUM(CASE WHEN m.tipo_movimiento IN ({$apartados}) THEN 0 ELSE m.cantidad END) as existencia")
            ->selectRaw("-SUM(CASE WHEN m.tipo_movimiento IN ({$apartados}) THEN m.cantidad ELSE 0 END) as apartado")
            ->selectRaw('SUM(m.cantidad) as disponible')
            ->selectRaw("SUM(CASE WHEN m.tipo_movimiento IN ({$apartados}) THEN 0 ELSE m.cantidad * m.costo_unitario END) as valor_inventario")
            ->selectRaw('MAX(m.aplicado_en) as ultimo_movimiento_en')
            ->where('m.estado', EstadoMovimiento::Aplicado->value)
            ->whereNull('m.deleted_at')
            ->whereNull('p.deleted_at');

        if ($porUbicacion) {
            $consulta->leftJoin('ubicaciones as u', 'u.id', '=', 'm.ubicacion_id')
                ->addSelect('m.ubicacion_id')
                ->selectRaw('u.codigo as ubicacion_codigo');

            $agrupar[] = 'm.ubicacion_id';
            $agrupar[] = 'u.codigo';
        }

        $this->aplicarFiltros($consulta, $filtros);

        return $consulta->groupBy($agrupar)
            ->orderBy('p.nombre')
            ->orderBy('a.codigo');
    }

    /** @param array<string, mixed> $filtros */
    private function aplicarFiltros(Builder $consulta, array $filtros): void
    {
        if (! empty($filtros['producto_id'])) {
            $consulta->where('m.producto_id', $filtros['producto_id']);
        }

        if (! empty($filtros['almacen_id'])) {
            $consulta->where('m.almacen_id', $filtros['almacen_id']);
        }

        if (! empty($filtros['categoria_ids'])) {
            $consulta->whereIn('p.categoria_id', $filtros['categoria_ids']);
        }

        if (! empty($filtros['buscar'])) {
            $termino = '%'.$filtros['buscar'].'%';
            $consulta->where(fn (Builder $filtro) => $filtro
                ->where('p.sku', 'like', $termino)
                ->orWhere('p.nombre', 'like', $termino));
        }

        // Un producto que entro y salio queda con existencia cero y no aporta
        // nada a la pantalla de existencias, pero si al kardex.
        if (! empty($filtros['solo_con_existencia'])) {
            $consulta->havingRaw('SUM(m.cantidad) <> 0');
        }
    }

    /**
     * La existencia DISPONIBLE de un producto en un almacen.
     *
     * Es literalmente la suma del libro, por lo que ya trae descontado lo
     * apartado. Es la cifra contra la que se valida toda salida.
     */
    public function disponible(int $productoId, int $almacenId): float
    {
        return (float) DB::table('movimientos_inventario')
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('estado', EstadoMovimiento::Aplicado->value)
            ->whereNull('deleted_at')
            ->sum('cantidad');
    }

    /** La existencia FISICA: lo que hay en el anaquel, apartado incluido. */
    public function fisica(int $productoId, int $almacenId): float
    {
        return (float) DB::table('movimientos_inventario')
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('estado', EstadoMovimiento::Aplicado->value)
            ->whereNotIn('tipo_movimiento', self::TIPOS_APARTADO)
            ->whereNull('deleted_at')
            ->sum('cantidad');
    }

    /** Lo comprometido con pedidos confirmados que aun no se surten. */
    public function apartado(int $productoId, int $almacenId): float
    {
        return -1 * (float) DB::table('movimientos_inventario')
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('estado', EstadoMovimiento::Aplicado->value)
            ->whereIn('tipo_movimiento', self::TIPOS_APARTADO)
            ->whereNull('deleted_at')
            ->sum('cantidad');
    }

    /**
     * El costo promedio ponderado de las entradas fisicas de un producto.
     *
     * Sirve para valorizar una salida cuando el documento no trae costo propio
     * (un ajuste negativo, un conteo). Si el producto nunca ha entrado, se cae
     * al costo del catalogo, que es lo unico que se sabe de el.
     */
    public function costoPromedio(int $productoId, ?int $almacenId = null): float
    {
        $entradas = DB::table('movimientos_inventario')
            ->where('producto_id', $productoId)
            ->where('estado', EstadoMovimiento::Aplicado->value)
            ->whereNotIn('tipo_movimiento', self::TIPOS_APARTADO)
            ->where('cantidad', '>', 0)
            ->whereNull('deleted_at')
            ->when($almacenId !== null, fn ($c) => $c->where('almacen_id', $almacenId))
            ->selectRaw('SUM(cantidad) as piezas, SUM(cantidad * costo_unitario) as valor')
            ->first();

        $piezas = (float) ($entradas->piezas ?? 0);

        if ($piezas > 0) {
            return round((float) $entradas->valor / $piezas, 6);
        }

        return (float) (DB::table('productos')->where('id', $productoId)->value('costo') ?? 0);
    }

    /**
     * Los productos por debajo de su minimo, segun reglas_reorden.
     *
     * Es la consulta que alimenta el reporte de stock bajo y el comando
     * `inventario:reorden`. Sale de una sola consulta con LEFT JOIN contra el
     * libro para que un producto SIN un solo movimiento -- existencia cero, el
     * caso mas urgente -- tambien aparezca.
     *
     * @return Collection<int, object>
     */
    public function bajoMinimo(?int $almacenId = null): Collection
    {
        $apartados = implode(',', array_map(fn (string $t) => "'".$t."'", self::TIPOS_APARTADO));

        return collect(DB::table('reglas_reorden as r')
            ->join('productos as p', 'p.id', '=', 'r.producto_id')
            ->join('almacenes as a', 'a.id', '=', 'r.almacen_id')
            ->leftJoin('movimientos_inventario as m', function ($union) {
                $union->on('m.producto_id', '=', 'r.producto_id')
                    ->on('m.almacen_id', '=', 'r.almacen_id')
                    ->where('m.estado', '=', EstadoMovimiento::Aplicado->value)
                    ->whereNull('m.deleted_at');
            })
            ->selectRaw('r.id as regla_id, r.cantidad_minima, r.cantidad_maxima, r.cantidad_reorden, r.dias_entrega')
            ->selectRaw('p.id as producto_id, p.sku, p.nombre as producto_nombre, p.costo')
            ->selectRaw('a.id as almacen_id, a.codigo as almacen_codigo, a.nombre as almacen_nombre')
            ->selectRaw('COALESCE(SUM(m.cantidad), 0) as disponible')
            ->selectRaw("COALESCE(SUM(CASE WHEN m.tipo_movimiento IN ({$apartados}) THEN 0 ELSE m.cantidad END), 0) as existencia")
            ->where('r.activo', true)
            ->whereNull('r.deleted_at')
            ->whereNull('p.deleted_at')
            ->when($almacenId !== null, fn ($c) => $c->where('r.almacen_id', $almacenId))
            ->groupBy('r.id', 'r.cantidad_minima', 'r.cantidad_maxima', 'r.cantidad_reorden', 'r.dias_entrega',
                'p.id', 'p.sku', 'p.nombre', 'p.costo', 'a.id', 'a.codigo', 'a.nombre')
            ->havingRaw('COALESCE(SUM(m.cantidad), 0) < r.cantidad_minima')
            ->orderBy('p.nombre')
            ->get());
    }
}
