<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoRequisicion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\Requisicion;
use App\Modules\Compras\Models\RequisicionLinea;
use App\Modules\Compras\Requests\ConvertirRequisicionRequest;
use App\Modules\Compras\Requests\GuardarRequisicionLineaRequest;
use App\Modules\Compras\Requests\GuardarRequisicionRequest;
use App\Modules\Compras\Requests\RevisarRequisicionRequest;
use App\Modules\Compras\Services\ServicioRequisicion;
use App\Modules\Inventario\Models\Producto;
use App\Modules\RH\Models\Departamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

/** Requisiciones: la peticion interna que arranca una compra. */
class RequisicionController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioRequisicion $servicio) {}

    public function index(Request $peticion): View
    {
        $requisiciones = Requisicion::query()
            ->with(['departamento', 'solicitante'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('departamento_id'),
                fn ($consulta) => $consulta->where('departamento_id', $peticion->integer('departamento_id')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras::paginas.requisiciones.index', [
            'requisiciones' => $requisiciones,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoRequisicion::class),
        ]);
    }

    public function create(): View
    {
        return view('compras::paginas.requisiciones.create', $this->datosDelFormulario(new Requisicion));
    }

    public function store(GuardarRequisicionRequest $peticion): RedirectResponse
    {
        $requisicion = Requisicion::create($peticion->validated() + ['solicitante_id' => Auth::id()]);

        return redirect()->route('compras.requisiciones.show', $requisicion)
            ->with('success', "Requisicion {$requisicion->numero_requisicion} creada. Agrega lo que necesitas.");
    }

    public function show(Requisicion $requisicion): View
    {
        $requisicion->load(['departamento', 'solicitante', 'lineas.producto', 'lineas.proveedorSugerido', 'ordenes']);

        return view('compras::paginas.requisiciones.show', [
            'requisicion' => $requisicion,
            'productos' => Producto::comprables()->orderBy('nombre')->get(),
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'bitacora' => $this->bitacoraDe($requisicion),
        ]);
    }

    public function edit(Requisicion $requisicion): View
    {
        abort_unless($requisicion->estado->esEditable(), 403);

        return view('compras::paginas.requisiciones.edit', $this->datosDelFormulario($requisicion));
    }

    public function update(GuardarRequisicionRequest $peticion, Requisicion $requisicion): RedirectResponse
    {
        abort_unless($requisicion->estado->esEditable(), 403);

        $requisicion->update($peticion->validated());

        return redirect()->route('compras.requisiciones.show', $requisicion)
            ->with('success', 'Requisicion actualizada correctamente.');
    }

    public function destroy(Requisicion $requisicion): RedirectResponse
    {
        abort_unless($requisicion->estado->esEditable(), 403);

        $requisicion->delete();

        return redirect()->route('compras.requisiciones.index')
            ->with('success', 'Requisicion eliminada correctamente.');
    }

    public function agregarLinea(GuardarRequisicionLineaRequest $peticion, Requisicion $requisicion): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($requisicion, $peticion->validated()),
            'Linea agregada a la requisicion.',
        );
    }

    public function eliminarLinea(Requisicion $requisicion, RequisicionLinea $linea): RedirectResponse
    {
        abort_unless($requisicion->estado->esEditable(), 403);
        abort_unless((int) $linea->requisicion_id === (int) $requisicion->id, 404);

        $linea->delete();

        return back()->with('success', 'Linea eliminada.');
    }

    public function enviar(Requisicion $requisicion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->enviar($requisicion),
            $requisicion,
            'compras.requisiciones.show',
            'Requisicion enviada a revision.',
        );
    }

    public function revisar(RevisarRequisicionRequest $peticion, Requisicion $requisicion): RedirectResponse
    {
        $aprobada = $peticion->apruebaLaRequisicion();

        return $this->ejecutarAccion(
            fn () => $this->servicio->revisar(
                $requisicion,
                Auth::user(),
                $aprobada,
                $peticion->input('comentario'),
            ),
            $requisicion,
            'compras.requisiciones.show',
            $aprobada ? 'Requisicion aprobada.' : 'Requisicion rechazada.',
        );
    }

    /**
     * Convierte en orden de compra y lleva a la orden nueva, no de vuelta a la
     * requisicion: lo siguiente que hay que hacer es ajustarle los precios.
     */
    public function convertir(ConvertirRequisicionRequest $peticion, Requisicion $requisicion): RedirectResponse
    {
        try {
            $orden = $this->servicio->convertirEnOrden(
                $requisicion,
                Proveedor::findOrFail($peticion->integer('proveedor_id')),
            );
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('compras.ordenes.show', $orden)
            ->with('success', "Se genero la orden {$orden->numero_orden}. Revisa los costos antes de confirmarla.");
    }

    public function cerrar(Requisicion $requisicion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cerrar($requisicion),
            $requisicion,
            'compras.requisiciones.show',
            'Requisicion cerrada.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Requisicion $requisicion): array
    {
        return [
            'requisicion' => $requisicion,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
        ];
    }
}
