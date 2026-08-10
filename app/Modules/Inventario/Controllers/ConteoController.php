<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Enums\EstadoConteo;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\ConteoInventario;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Requests\CapturarConteoRequest;
use App\Modules\Inventario\Requests\GuardarConteoRequest;
use App\Modules\Inventario\Services\ServicioConteo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Conteos fisicos: iniciar, capturar y cerrar. */
class ConteoController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioConteo $servicio) {}

    public function index(Request $peticion): View
    {
        $conteos = ConteoInventario::query()
            ->with(['almacen', 'ubicacion'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::paginas.conteos.index', [
            'conteos' => $conteos,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'estados' => OpcionesEnum::de(EstadoConteo::class),
        ]);
    }

    public function create(): View
    {
        return view('inventario::paginas.conteos.create', [
            'conteo' => new ConteoInventario,
            'almacenes' => Almacen::activos()->with('ubicaciones')->orderBy('codigo')->get(),
        ]);
    }

    public function store(GuardarConteoRequest $peticion): RedirectResponse
    {
        $conteo = ConteoInventario::create($peticion->validated());

        return redirect()->route('inventario.conteos.show', $conteo)
            ->with('success', "Conteo {$conteo->numero_conteo} creado. Inicialo para congelar las existencias.");
    }

    public function show(ConteoInventario $conteo): View
    {
        $conteo->load(['almacen', 'ubicacion', 'lineas.producto', 'contadoPor', 'cerradoPor']);

        return view('inventario::paginas.conteos.show', [
            'conteo' => $conteo,
            'productos' => Producto::activos()->where('es_inventariable', true)->orderBy('nombre')->get(),
            'bitacora' => $this->bitacoraDe($conteo),
        ]);
    }

    public function edit(ConteoInventario $conteo): View
    {
        abort_unless($conteo->estado->esEditable(), 403);

        return view('inventario::paginas.conteos.edit', [
            'conteo' => $conteo,
            'almacenes' => Almacen::activos()->with('ubicaciones')->orderBy('codigo')->get(),
        ]);
    }

    public function update(GuardarConteoRequest $peticion, ConteoInventario $conteo): RedirectResponse
    {
        abort_unless($conteo->estado->esEditable(), 403);

        $conteo->update($peticion->validated());

        return redirect()->route('inventario.conteos.show', $conteo)
            ->with('success', 'Conteo actualizado correctamente.');
    }

    public function destroy(ConteoInventario $conteo): RedirectResponse
    {
        abort_unless($conteo->estado->esEditable(), 403);

        $conteo->delete();

        return redirect()->route('inventario.conteos.index')
            ->with('success', 'Conteo eliminado correctamente.');
    }

    public function iniciar(ConteoInventario $conteo): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->iniciar($conteo, Auth::user()),
            $conteo,
            'inventario.conteos.show',
            'Conteo iniciado: la existencia esperada quedo congelada.',
        );
    }

    public function capturar(CapturarConteoRequest $peticion, ConteoInventario $conteo): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->capturar($conteo, $peticion->cantidades()),
            $conteo,
            'inventario.conteos.show',
            'Cantidades guardadas.',
        );
    }

    public function agregarProducto(Request $peticion, ConteoInventario $conteo): RedirectResponse
    {
        $peticion->validate(['producto_id' => ['required', 'integer', 'exists:productos,id']]);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarProducto($conteo, Producto::findOrFail($peticion->integer('producto_id'))),
            'Producto agregado al conteo.',
        );
    }

    public function cerrar(ConteoInventario $conteo): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cerrar($conteo, Auth::user()),
            $conteo,
            'inventario.conteos.show',
            'Conteo cerrado: las diferencias ya son movimientos de inventario.',
        );
    }
}
