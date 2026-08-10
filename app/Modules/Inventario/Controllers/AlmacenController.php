<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Requests\GuardarAlmacenRequest;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Los almacenes fisicos y, desde su ficha, sus ubicaciones. */
class AlmacenController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function index(Request $peticion): View
    {
        $almacenes = Almacen::query()
            ->withCount('ubicaciones')
            ->buscar($peticion->string('buscar')->toString())
            ->orderBy('codigo')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::catalogos.almacenes.index', ['almacenes' => $almacenes]);
    }

    public function create(): View
    {
        return view('inventario::catalogos.almacenes.create', ['almacen' => new Almacen]);
    }

    public function store(GuardarAlmacenRequest $peticion): RedirectResponse
    {
        $almacen = Almacen::create($peticion->validated());

        return redirect()->route('inventario.almacenes.show', $almacen)
            ->with('success', 'Almacen registrado correctamente.');
    }

    public function show(Almacen $almacen): View
    {
        $almacen->load(['ubicaciones' => fn ($consulta) => $consulta->orderBy('codigo')]);

        return view('inventario::catalogos.almacenes.show', [
            'almacen' => $almacen,
            'existencias' => $this->existencias
                ->consulta(['almacen_id' => $almacen->id, 'solo_con_existencia' => true])
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(Almacen $almacen): View
    {
        return view('inventario::catalogos.almacenes.edit', ['almacen' => $almacen]);
    }

    public function update(GuardarAlmacenRequest $peticion, Almacen $almacen): RedirectResponse
    {
        $almacen->update($peticion->validated());

        return redirect()->route('inventario.almacenes.show', $almacen)
            ->with('success', 'Almacen actualizado correctamente.');
    }

    public function destroy(Almacen $almacen): RedirectResponse
    {
        if ($almacen->movimientos()->exists()) {
            return back()->with('error',
                'Ese almacen ya tiene movimientos. Marcalo como inactivo en vez de eliminarlo.');
        }

        $almacen->delete();

        return redirect()->route('inventario.almacenes.index')
            ->with('success', 'Almacen dado de baja correctamente.');
    }
}
