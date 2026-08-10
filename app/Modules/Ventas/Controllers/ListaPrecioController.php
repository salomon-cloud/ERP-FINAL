<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Models\ListaPrecioItem;
use App\Modules\Ventas\Requests\GuardarListaPrecioItemRequest;
use App\Modules\Ventas\Requests\GuardarListaPrecioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Listas de precios y sus escalones por volumen.
 *
 * Los precios se administran desde la ficha de la lista: es donde tienen
 * sentido y donde se ven unos junto a otros.
 */
class ListaPrecioController extends Controller
{
    public function index(Request $peticion): View
    {
        $listas = ListaPrecio::query()
            ->withCount(['items', 'clientes'])
            ->when($peticion->filled('buscar'), fn ($consulta) => $consulta
                ->where(fn ($filtro) => $filtro
                    ->where('codigo', 'like', '%'.$peticion->string('buscar').'%')
                    ->orWhere('nombre', 'like', '%'.$peticion->string('buscar').'%')))
            ->orderByDesc('es_predeterminada')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::catalogos.listas-precios.index', ['listas' => $listas]);
    }

    public function create(): View
    {
        return view('ventas::catalogos.listas-precios.create', ['lista' => new ListaPrecio]);
    }

    public function store(GuardarListaPrecioRequest $peticion): RedirectResponse
    {
        $lista = $this->guardar(new ListaPrecio, $peticion->validated());

        return redirect()->route('ventas.listas-precios.show', $lista)
            ->with('success', 'Lista de precios creada. Agrega sus productos.');
    }

    public function show(ListaPrecio $lista): View
    {
        $lista->load(['items.producto' => fn ($consulta) => $consulta->orderBy('nombre')]);

        return view('ventas::catalogos.listas-precios.show', [
            'lista' => $lista,
            'productos' => Producto::vendibles()->orderBy('nombre')->get(),
        ]);
    }

    public function edit(ListaPrecio $lista): View
    {
        return view('ventas::catalogos.listas-precios.edit', ['lista' => $lista]);
    }

    public function update(GuardarListaPrecioRequest $peticion, ListaPrecio $lista): RedirectResponse
    {
        $this->guardar($lista, $peticion->validated());

        return redirect()->route('ventas.listas-precios.show', $lista)
            ->with('success', 'Lista de precios actualizada.');
    }

    public function destroy(ListaPrecio $lista): RedirectResponse
    {
        if ($lista->clientes()->exists()) {
            return back()->with('error', 'Esa lista la usan clientes del catalogo. Reasignalos primero.');
        }

        $lista->delete();

        return redirect()->route('ventas.listas-precios.index')
            ->with('success', 'Lista de precios eliminada.');
    }

    public function agregarItem(GuardarListaPrecioItemRequest $peticion, ListaPrecio $lista): RedirectResponse
    {
        $lista->items()->create($peticion->validated());

        return back()->with('success', 'Precio agregado a la lista.');
    }

    public function eliminarItem(ListaPrecio $lista, ListaPrecioItem $item): RedirectResponse
    {
        abort_unless((int) $item->lista_precio_id === (int) $lista->id, 404);

        $item->delete();

        return back()->with('success', 'Precio eliminado de la lista.');
    }

    /**
     * Guarda la lista cuidando que solo UNA sea la predeterminada.
     *
     * Dos predeterminadas dejarian a ServicioResolverPrecio eligiendo al azar
     * cual aplicar, y el precio de un mismo producto cambiaria sin explicacion.
     *
     * @param  array<string, mixed>  $datos
     */
    private function guardar(ListaPrecio $lista, array $datos): ListaPrecio
    {
        return DB::transaction(function () use ($lista, $datos): ListaPrecio {
            $lista->fill($datos)->save();

            if ($datos['es_predeterminada']) {
                ListaPrecio::query()
                    ->whereKeyNot($lista->getKey())
                    ->update(['es_predeterminada' => false]);
            }

            return $lista->refresh();
        });
    }
}
