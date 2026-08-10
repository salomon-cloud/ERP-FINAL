<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\UnidadMedida;
use App\Modules\Inventario\Requests\GuardarUnidadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Unidades de medida y su factor de conversion.
 *
 * El inventario se guarda siempre en unidad base; esta pantalla define cuanto
 * vale cada unidad de compra o de venta en esa base.
 */
class UnidadController extends Controller
{
    public function index(): View
    {
        return view('inventario::catalogos.unidades.index', [
            'unidades' => UnidadMedida::withCount('productos')->orderBy('codigo')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('inventario::catalogos.unidades.create', ['unidad' => new UnidadMedida]);
    }

    public function store(GuardarUnidadRequest $peticion): RedirectResponse
    {
        UnidadMedida::create($peticion->validated());

        return redirect()->route('inventario.unidades.index')
            ->with('success', 'Unidad de medida registrada correctamente.');
    }

    public function edit(UnidadMedida $unidad): View
    {
        return view('inventario::catalogos.unidades.edit', ['unidad' => $unidad]);
    }

    public function update(GuardarUnidadRequest $peticion, UnidadMedida $unidad): RedirectResponse
    {
        $unidad->update($peticion->validated());

        return redirect()->route('inventario.unidades.index')
            ->with('success', 'Unidad de medida actualizada correctamente.');
    }

    public function destroy(UnidadMedida $unidad): RedirectResponse
    {
        if ($unidad->productos()->exists()) {
            return back()->with('error', 'Esa unidad la usan productos del catalogo.');
        }

        $unidad->delete();

        return redirect()->route('inventario.unidades.index')
            ->with('success', 'Unidad de medida eliminada correctamente.');
    }
}
