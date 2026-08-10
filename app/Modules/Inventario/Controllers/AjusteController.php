<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Enums\EstadoAjuste;
use App\Modules\Inventario\Models\AjusteInventario;
use App\Modules\Inventario\Models\AjusteLinea;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Requests\GuardarAjusteLineaRequest;
use App\Modules\Inventario\Requests\GuardarAjusteRequest;
use App\Modules\Inventario\Services\ServicioAjusteInventario;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ajustes de inventario: merma, rotura, sobrante, error de captura.
 *
 * Capturar el ajuste basta con `inventario.ajustes.crear`; aplicarlo exige
 * `inventario.ajustes.aprobar`. Son dos actos distintos a proposito.
 */
class AjusteController extends Controller
{
    use ControlaDocumentos;

    public function __construct(
        private readonly ServicioAjusteInventario $servicio,
        private readonly ServicioExistencias $existencias,
    ) {}

    public function index(Request $peticion): View
    {
        $ajustes = AjusteInventario::query()
            ->withCount('lineas')
            ->with('aprobadoPor')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::paginas.ajustes.index', [
            'ajustes' => $ajustes,
            'estados' => OpcionesEnum::de(EstadoAjuste::class),
        ]);
    }

    public function create(): View
    {
        return view('inventario::paginas.ajustes.create', ['ajuste' => new AjusteInventario]);
    }

    public function store(GuardarAjusteRequest $peticion): RedirectResponse
    {
        $ajuste = AjusteInventario::create($peticion->validated());

        return redirect()->route('inventario.ajustes.show', $ajuste)
            ->with('success', "Ajuste {$ajuste->numero_ajuste} creado. Agrega sus lineas.");
    }

    public function show(AjusteInventario $ajuste): View
    {
        $ajuste->load(['lineas.producto', 'lineas.almacen', 'lineas.ubicacion', 'aprobadoPor']);

        return view('inventario::paginas.ajustes.show', [
            'ajuste' => $ajuste,
            'productos' => Producto::activos()->where('es_inventariable', true)->orderBy('nombre')->get(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            // El impacto en pesos, para que quien autoriza sepa que esta firmando.
            'impacto' => $this->impactoDe($ajuste),
            'bitacora' => $this->bitacoraDe($ajuste),
        ]);
    }

    public function edit(AjusteInventario $ajuste): View
    {
        abort_unless($ajuste->estado->esEditable(), 403);

        return view('inventario::paginas.ajustes.edit', ['ajuste' => $ajuste]);
    }

    public function update(GuardarAjusteRequest $peticion, AjusteInventario $ajuste): RedirectResponse
    {
        abort_unless($ajuste->estado->esEditable(), 403);

        $ajuste->update($peticion->validated());

        return redirect()->route('inventario.ajustes.show', $ajuste)
            ->with('success', 'Ajuste actualizado correctamente.');
    }

    public function destroy(AjusteInventario $ajuste): RedirectResponse
    {
        abort_unless($ajuste->estado->esEditable(), 403);

        $ajuste->delete();

        return redirect()->route('inventario.ajustes.index')
            ->with('success', 'Ajuste eliminado correctamente.');
    }

    public function agregarLinea(GuardarAjusteLineaRequest $peticion, AjusteInventario $ajuste): RedirectResponse
    {
        abort_unless($ajuste->estado->esEditable(), 403);

        $datos = $peticion->validated();

        // Sin costo capturado se valoriza al promedio: dar de baja mercancia al
        // precio de lista y no a lo que costo distorsiona el inventario.
        if (blank($datos['costo_unitario'] ?? null)) {
            $datos['costo_unitario'] = $this->existencias
                ->costoPromedio((int) $datos['producto_id'], (int) $datos['almacen_id']);
        }

        $ajuste->lineas()->create($datos);

        return back()->with('success', 'Linea agregada al ajuste.');
    }

    public function eliminarLinea(AjusteInventario $ajuste, AjusteLinea $linea): RedirectResponse
    {
        abort_unless($ajuste->estado->esEditable(), 403);
        abort_unless((int) $linea->ajuste_id === (int) $ajuste->id, 404);

        $linea->delete();

        return back()->with('success', 'Linea eliminada.');
    }

    public function aplicar(AjusteInventario $ajuste): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->aplicar($ajuste, Auth::user()),
            $ajuste,
            'inventario.ajustes.show',
            'Ajuste aplicado: el inventario ya refleja las diferencias.',
        );
    }

    public function cancelar(AjusteInventario $ajuste): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($ajuste),
            $ajuste,
            'inventario.ajustes.show',
            'Ajuste cancelado.',
        );
    }

    /** Lo que el ajuste va a sumar o restar al valor del inventario. */
    private function impactoDe(AjusteInventario $ajuste): float
    {
        return $ajuste->lineas->sum(
            fn (AjusteLinea $linea) => (float) $linea->diferencia * (float) $linea->costo_unitario
        );
    }
}
