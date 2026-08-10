<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\ReglaReorden;
use App\Modules\Inventario\Requests\GuardarReglaReordenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Los minimos y maximos por almacen: lo que hace que el comando
 * `inventario:reorden` tenga contra que comparar.
 *
 * Sin una regla no hay alerta. `productos.stock_minimo` es el minimo global del
 * catalogo y sirve de referencia, pero el reorden real es por almacen: no se
 * surte igual la bodega central que un mostrador.
 */
class ReglaReordenController extends Controller
{
    public function index(Request $peticion): View
    {
        $reglas = ReglaReorden::query()
            ->with(['producto', 'almacen'])
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->when($peticion->filled('buscar'), fn ($consulta) => $consulta
                ->whereHas('producto', fn ($producto) => $producto
                    ->buscar($peticion->string('buscar')->toString())))
            ->orderBy('almacen_id')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::paginas.reglas-reorden.index', [
            'reglas' => $reglas,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ]);
    }

    public function create(): View
    {
        return view('inventario::paginas.reglas-reorden.create', $this->datosDelFormulario(new ReglaReorden));
    }

    public function store(GuardarReglaReordenRequest $peticion): RedirectResponse
    {
        ReglaReorden::create($peticion->validated());

        return redirect()->route('inventario.reglas-reorden.index')
            ->with('success', 'Regla de reorden registrada correctamente.');
    }

    public function edit(ReglaReorden $regla): View
    {
        return view('inventario::paginas.reglas-reorden.edit', $this->datosDelFormulario($regla));
    }

    public function update(GuardarReglaReordenRequest $peticion, ReglaReorden $regla): RedirectResponse
    {
        $regla->update($peticion->validated());

        return redirect()->route('inventario.reglas-reorden.index')
            ->with('success', 'Regla de reorden actualizada correctamente.');
    }

    public function destroy(ReglaReorden $regla): RedirectResponse
    {
        $regla->delete();

        return redirect()->route('inventario.reglas-reorden.index')
            ->with('success', 'Regla de reorden eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(ReglaReorden $regla): array
    {
        return [
            'regla' => $regla,
            'productos' => Producto::activos()->orderBy('nombre')->get(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ];
    }
}
