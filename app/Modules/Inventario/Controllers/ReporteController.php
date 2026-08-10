<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\ExportadorCsv;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\MovimientoInventario;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los reportes de Inventario.
 *
 * Todos son de solo lectura y todos aceptan `?formato=csv`, que descarga
 * EXACTAMENTE los mismos datos que se ven en pantalla: la consulta es una sola
 * y solo cambia como se presenta. Es el patron que ya usa RH.
 */
class ReporteController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function index(): View
    {
        return view('inventario::paginas.reportes.index');
    }

    /** Existencias valorizadas por producto y almacen. */
    public function existencias(Request $peticion): View|StreamedResponse
    {
        $filas = $this->existencias->consulta([
            'almacen_id' => $peticion->integer('almacen_id') ?: null,
            'categoria_ids' => $this->ramaDeCategoria($peticion),
            'buscar' => $peticion->string('buscar')->toString(),
            'solo_con_existencia' => ! $peticion->boolean('incluir_en_cero'),
        ])->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('inventario', 'existencias',
                ['SKU', 'Producto', 'Almacen', 'Existencia', 'Apartado', 'Disponible', 'Costo', 'Valor'],
                $filas->map(fn ($fila) => [
                    $fila->sku,
                    $fila->producto_nombre,
                    $fila->almacen_nombre,
                    (float) $fila->existencia,
                    (float) $fila->apartado,
                    (float) $fila->disponible,
                    (float) $fila->costo,
                    (float) $fila->valor_inventario,
                ]));
        }

        return view('inventario::paginas.reportes.existencias', [
            'filas' => $filas,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'categorias' => CategoriaProducto::activos()->orderBy('nombre')->get(),
            'valorTotal' => $filas->sum(fn ($fila) => (float) $fila->valor_inventario),
        ]);
    }

    /** El kardex de un periodo: la historia completa, movimiento a movimiento. */
    public function movimientos(Request $peticion): View|StreamedResponse
    {
        $filas = MovimientoInventario::query()
            ->with(['producto', 'almacen', 'ubicacion', 'aplicadoPor'])
            ->aplicados()
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->when($peticion->filled('tipo_movimiento'),
                fn ($consulta) => $consulta->where('tipo_movimiento', $peticion->input('tipo_movimiento')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('aplicado_en', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('aplicado_en', '<=', $peticion->date('hasta')))
            ->orderByDesc('aplicado_en')
            ->limit(2000)
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('inventario', 'movimientos',
                ['Fecha', 'Tipo', 'SKU', 'Producto', 'Almacen', 'Ubicacion', 'Cantidad', 'Costo', 'Documento', 'Usuario'],
                $filas->map(fn (MovimientoInventario $m) => [
                    $m->aplicado_en?->format('d/m/Y H:i'),
                    $m->tipo_movimiento->label(),
                    $m->producto?->sku,
                    $m->producto?->nombre,
                    $m->almacen?->nombre,
                    $m->ubicacion?->codigo,
                    (float) $m->cantidad,
                    (float) $m->costo_unitario,
                    $m->origen_tipo ? $m->origen_tipo.' #'.$m->origen_id : '',
                    $m->aplicadoPor?->name,
                ]));
        }

        return view('inventario::paginas.reportes.movimientos', [
            'filas' => $filas,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'tipos' => OpcionesEnum::de(TipoMovimiento::class),
        ]);
    }

    /** Lo que hay que comprar: disponible por debajo del minimo del almacen. */
    public function stockBajo(Request $peticion): View|StreamedResponse
    {
        $filas = $this->existencias->bajoMinimo($peticion->integer('almacen_id') ?: null);

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('inventario', 'stock-bajo',
                ['SKU', 'Producto', 'Almacen', 'Disponible', 'Minimo', 'Maximo', 'Sugerido', 'Dias entrega'],
                $filas->map(fn ($fila) => [
                    $fila->sku,
                    $fila->producto_nombre,
                    $fila->almacen_nombre,
                    (float) $fila->disponible,
                    (float) $fila->cantidad_minima,
                    (float) $fila->cantidad_maxima,
                    $this->sugerido($fila),
                    (int) $fila->dias_entrega,
                ]));
        }

        return view('inventario::paginas.reportes.stock-bajo', [
            'filas' => $filas,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ]);
    }

    /** Valoracion del inventario agrupada por categoria. */
    public function valoracion(Request $peticion): View|StreamedResponse
    {
        $existencias = $this->existencias->consulta(['solo_con_existencia' => true])->get();

        $categorias = CategoriaProducto::pluck('nombre', 'id');

        $filas = $existencias
            ->groupBy(fn ($fila) => $fila->categoria_id ?? 0)
            ->map(fn ($grupo, $categoriaId) => (object) [
                'categoria' => $categorias[$categoriaId] ?? 'Sin categoria',
                'productos' => $grupo->unique('producto_id')->count(),
                'piezas' => $grupo->sum(fn ($fila) => (float) $fila->existencia),
                'valor' => $grupo->sum(fn ($fila) => (float) $fila->valor_inventario),
            ])
            ->sortByDesc('valor')
            ->values();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('inventario', 'valoracion',
                ['Categoria', 'Productos', 'Piezas', 'Valor'],
                $filas->map(fn ($fila) => [$fila->categoria, $fila->productos, $fila->piezas, $fila->valor]));
        }

        return view('inventario::paginas.reportes.valoracion', [
            'filas' => $filas,
            'valorTotal' => $filas->sum('valor'),
        ]);
    }

    /** Lotes caducados o a punto de caducar. */
    public function caducidades(Request $peticion): View|StreamedResponse
    {
        $dias = $peticion->integer('dias') ?: 30;

        $filas = Lote::query()
            ->with('producto')
            ->activos()
            ->porVencer($dias)
            ->orderBy('fecha_caducidad')
            ->get();

        if ($peticion->input('formato') === 'csv') {
            return ExportadorCsv::descargar('inventario', 'caducidades',
                ['SKU', 'Producto', 'Lote', 'Caducidad', 'Dias restantes'],
                $filas->map(fn (Lote $lote) => [
                    $lote->producto?->sku,
                    $lote->producto?->nombre,
                    $lote->numero_lote,
                    $lote->fecha_caducidad?->format('d/m/Y'),
                    $lote->fecha_caducidad !== null
                        ? (int) now()->startOfDay()->diffInDays($lote->fecha_caducidad, false)
                        : '',
                ]));
        }

        return view('inventario::paginas.reportes.caducidades', ['filas' => $filas, 'dias' => $dias]);
    }

    /** La rama completa de la categoria filtrada, o null si no se filtro. */
    private function ramaDeCategoria(Request $peticion): ?array
    {
        if (! $peticion->filled('categoria_id')) {
            return null;
        }

        return CategoriaProducto::with('hijas.hijas')
            ->find($peticion->integer('categoria_id'))?->idsDeLaRama() ?? [];
    }

    private function sugerido(object $fila): float
    {
        $reorden = (float) $fila->cantidad_reorden;

        if ($reorden > 0) {
            return $reorden;
        }

        $maximo = (float) $fila->cantidad_maxima;
        $objetivo = $maximo > 0 ? $maximo : (float) $fila->cantidad_minima;

        return max($objetivo - (float) $fila->disponible, 0.0);
    }
}
