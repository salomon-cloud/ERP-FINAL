<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Ubicacion;
use App\Modules\Inventario\Requests\GuardarUbicacionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pasillos y anaqueles. Se administran desde la ficha del almacen. */
class UbicacionController extends Controller
{
    public function index(Request $peticion): View
    {
        $ubicaciones = Ubicacion::query()
            ->with('almacen')
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->when($peticion->filled('buscar'), fn ($consulta) => $consulta
                ->where(fn ($filtro) => $filtro
                    ->where('codigo', 'like', '%'.$peticion->string('buscar').'%')
                    ->orWhere('nombre', 'like', '%'.$peticion->string('buscar').'%')))
            ->orderBy('almacen_id')
            ->orderBy('codigo')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::catalogos.ubicaciones.index', [
            'ubicaciones' => $ubicaciones,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ]);
    }

    public function create(Request $peticion): View
    {
        $ubicacion = new Ubicacion(['almacen_id' => $peticion->integer('almacen_id') ?: null]);

        return view('inventario::catalogos.ubicaciones.create', $this->datosDelFormulario($ubicacion));
    }

    public function store(GuardarUbicacionRequest $peticion): RedirectResponse
    {
        $ubicacion = Ubicacion::create($peticion->validated());

        return redirect()->route('inventario.almacenes.show', $ubicacion->almacen_id)
            ->with('success', 'Ubicacion registrada correctamente.');
    }

    public function edit(Ubicacion $ubicacion): View
    {
        return view('inventario::catalogos.ubicaciones.edit', $this->datosDelFormulario($ubicacion));
    }

    public function update(GuardarUbicacionRequest $peticion, Ubicacion $ubicacion): RedirectResponse
    {
        $ubicacion->update($peticion->validated());

        return redirect()->route('inventario.almacenes.show', $ubicacion->almacen_id)
            ->with('success', 'Ubicacion actualizada correctamente.');
    }

    public function destroy(Ubicacion $ubicacion): RedirectResponse
    {
        $almacenId = $ubicacion->almacen_id;
        $ubicacion->delete();

        return redirect()->route('inventario.almacenes.show', $almacenId)
            ->with('success', 'Ubicacion eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Ubicacion $ubicacion): array
    {
        return [
            'ubicacion' => $ubicacion,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ];
    }
}
