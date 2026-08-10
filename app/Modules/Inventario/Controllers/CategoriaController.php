<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Requests\GuardarCategoriaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** El arbol de categorias: lo que convierte un catalogo en varios sin duplicarlo. */
class CategoriaController extends Controller
{
    public function index(Request $peticion): View
    {
        $categorias = CategoriaProducto::query()
            ->with('padre')
            ->withCount('productos')
            ->when($peticion->filled('buscar'), fn ($consulta) => $consulta
                ->where(fn ($filtro) => $filtro
                    ->where('codigo', 'like', '%'.$peticion->string('buscar').'%')
                    ->orWhere('nombre', 'like', '%'.$peticion->string('buscar').'%')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::catalogos.categorias.index', ['categorias' => $categorias]);
    }

    public function create(): View
    {
        return view('inventario::catalogos.categorias.create', $this->datosDelFormulario(new CategoriaProducto));
    }

    public function store(GuardarCategoriaRequest $peticion): RedirectResponse
    {
        CategoriaProducto::create($peticion->validated());

        return redirect()->route('inventario.categorias.index')
            ->with('success', 'Categoria registrada correctamente.');
    }

    public function edit(CategoriaProducto $categoria): View
    {
        return view('inventario::catalogos.categorias.edit', $this->datosDelFormulario($categoria));
    }

    public function update(GuardarCategoriaRequest $peticion, CategoriaProducto $categoria): RedirectResponse
    {
        $categoria->update($peticion->validated());

        return redirect()->route('inventario.categorias.index')
            ->with('success', 'Categoria actualizada correctamente.');
    }

    public function destroy(CategoriaProducto $categoria): RedirectResponse
    {
        if ($categoria->productos()->exists() || $categoria->hijas()->exists()) {
            return back()->with('error',
                'Esa categoria tiene productos o subcategorias. Reasignalos antes de eliminarla.');
        }

        $categoria->delete();

        return redirect()->route('inventario.categorias.index')
            ->with('success', 'Categoria eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(CategoriaProducto $categoria): array
    {
        return [
            'categoria' => $categoria,
            // Ninguna categoria puede ser su propia madre.
            'padres' => CategoriaProducto::activos()
                ->whereKeyNot($categoria->getKey())
                ->orderBy('nombre')
                ->get(),
        ];
    }
}
