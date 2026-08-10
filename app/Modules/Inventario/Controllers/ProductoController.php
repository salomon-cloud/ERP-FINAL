<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\UnidadMedida;
use App\Modules\Inventario\Requests\GuardarProductoRequest;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * EL catalogo unificado de productos.
 *
 * No hay una pantalla para medicamentos, otra para insumos y otra para
 * papeleria: hay UNA con pestanas por categoria (docs/david.md D4). Cinco
 * pantallas serian cinco veces la misma logica y cinco sitios donde arreglar el
 * mismo error.
 */
class ProductoController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function index(Request $peticion): View
    {
        $productos = Producto::query()
            ->with(['categoria', 'unidad'])
            ->buscar($peticion->string('buscar')->toString())
            ->deLaCategoria($peticion->filled('categoria_id') ? $peticion->integer('categoria_id') : null)
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::catalogos.productos.index', [
            'productos' => $productos,
            'categorias' => $this->categoriasEnArbol(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
            // La existencia total de los productos de la pagina, en una sola
            // consulta: pedirsela al servicio producto por producto seria un
            // N+1 con la pantalla mas visitada del modulo.
            'existencias' => $this->existenciasDe($productos->pluck('id')->all()),
        ]);
    }

    public function create(): View
    {
        return view('inventario::catalogos.productos.create', $this->datosDelFormulario(new Producto));
    }

    public function store(GuardarProductoRequest $peticion): RedirectResponse
    {
        $producto = Producto::create($peticion->validated());

        return redirect()->route('inventario.productos.show', $producto)
            ->with('success', 'Producto registrado correctamente.');
    }

    public function show(Producto $producto): View
    {
        $producto->load(['categoria', 'unidad', 'codigosBarras', 'lotes']);

        return view('inventario::catalogos.productos.show', [
            'producto' => $producto,
            'existencias' => $this->existencias->consulta(['producto_id' => $producto->id])->get(),
            'movimientos' => $producto->movimientos()
                ->with(['almacen', 'ubicacion', 'aplicadoPor'])
                ->orderByDesc('aplicado_en')
                ->limit(15)
                ->get(),
        ]);
    }

    public function edit(Producto $producto): View
    {
        return view('inventario::catalogos.productos.edit', $this->datosDelFormulario($producto));
    }

    public function update(GuardarProductoRequest $peticion, Producto $producto): RedirectResponse
    {
        $producto->update($peticion->validated());

        return redirect()->route('inventario.productos.show', $producto)
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Baja logica. Las llaves foraneas de los documentos son RESTRICT, asi que
     * un producto con historia no se puede borrar de verdad -- y no debe: sus
     * movimientos y sus lineas de factura seguirian apuntando a el.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->movimientos()->exists()) {
            return back()->with('error',
                'Ese producto ya tiene movimientos de inventario. Marcalo como inactivo en vez de eliminarlo.');
        }

        $producto->delete();

        return redirect()->route('inventario.productos.index')
            ->with('success', 'Producto dado de baja correctamente.');
    }

    /**
     * La existencia disponible total (todos los almacenes) de un puñado de
     * productos, en una consulta.
     *
     * @param  array<int, int>  $productoIds
     * @return array<int, float>
     */
    private function existenciasDe(array $productoIds): array
    {
        if ($productoIds === []) {
            return [];
        }

        return DB::table('movimientos_inventario')
            ->select('producto_id')
            ->selectRaw('SUM(cantidad) as total')
            ->whereIn('producto_id', $productoIds)
            ->where('estado', 'aplicado')
            ->whereNull('deleted_at')
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id')
            ->map(fn ($cantidad) => (float) $cantidad)
            ->all();
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Producto $producto): array
    {
        return [
            'producto' => $producto,
            'categorias' => $this->categoriasEnArbol(),
            'unidades' => UnidadMedida::orderBy('codigo')->get(),
            'impuestos' => DB::table('impuestos')
                ->whereNull('deleted_at')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre', 'tasa']),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }

    /**
     * Las categorias activas con su rama, para pintar "Insumos / Curacion" en
     * un solo <select> sin armar un arbol en la vista.
     */
    private function categoriasEnArbol()
    {
        return CategoriaProducto::activos()->with('padre')->orderBy('nombre')->get();
    }
}
