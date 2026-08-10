<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Enums\EstadoTraspaso;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\Traspaso;
use App\Modules\Inventario\Models\TraspasoLinea;
use App\Modules\Inventario\Requests\GuardarTraspasoLineaRequest;
use App\Modules\Inventario\Requests\GuardarTraspasoRequest;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Inventario\Services\ServicioTraspaso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Traspasos entre almacenes.
 *
 * El documento se captura en dos pasos -- primero la cabecera, luego las lineas
 * en su ficha -- y no con un formulario gigante que se pierda entero si algo
 * falla. Es el mismo patron en los documentos de los tres modulos.
 */
class TraspasoController extends Controller
{
    use ControlaDocumentos;

    public function __construct(
        private readonly ServicioTraspaso $servicio,
        private readonly ServicioExistencias $existencias,
    ) {}

    public function index(Request $peticion): View
    {
        $traspasos = Traspaso::query()
            ->with(['almacenOrigen', 'almacenDestino'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('almacen_id'), fn ($consulta) => $consulta
                ->where(fn ($filtro) => $filtro
                    ->where('almacen_origen_id', $peticion->integer('almacen_id'))
                    ->orWhere('almacen_destino_id', $peticion->integer('almacen_id'))))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('inventario::paginas.traspasos.index', [
            'traspasos' => $traspasos,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'estados' => OpcionesEnum::de(EstadoTraspaso::class),
        ]);
    }

    public function create(): View
    {
        return view('inventario::paginas.traspasos.create', [
            'traspaso' => new Traspaso,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ]);
    }

    public function store(GuardarTraspasoRequest $peticion): RedirectResponse
    {
        $traspaso = Traspaso::create($peticion->validated() + ['solicitado_por' => Auth::id()]);

        return redirect()->route('inventario.traspasos.show', $traspaso)
            ->with('success', "Traspaso {$traspaso->numero_traspaso} creado. Agrega sus lineas.");
    }

    public function show(Traspaso $traspaso): View
    {
        $traspaso->load([
            'almacenOrigen', 'almacenDestino', 'lineas.producto', 'lineas.lote',
            'solicitadoPor', 'aprobadoPor',
        ]);

        return view('inventario::paginas.traspasos.show', [
            'traspaso' => $traspaso,
            'productos' => Producto::activos()->where('es_inventariable', true)->orderBy('nombre')->get(),
            // Lo disponible en el origen, para que el capturista vea antes de
            // enviar si el traspaso va a caber.
            'disponibles' => $this->disponiblesEnOrigen($traspaso),
            'bitacora' => $this->bitacoraDe($traspaso),
        ]);
    }

    public function edit(Traspaso $traspaso): View
    {
        abort_unless($traspaso->estado->esEditable(), 403);

        return view('inventario::paginas.traspasos.edit', [
            'traspaso' => $traspaso,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
        ]);
    }

    public function update(GuardarTraspasoRequest $peticion, Traspaso $traspaso): RedirectResponse
    {
        abort_unless($traspaso->estado->esEditable(), 403);

        $traspaso->update($peticion->validated());

        return redirect()->route('inventario.traspasos.show', $traspaso)
            ->with('success', 'Traspaso actualizado correctamente.');
    }

    public function destroy(Traspaso $traspaso): RedirectResponse
    {
        abort_unless($traspaso->estado->esEditable(), 403);

        $traspaso->delete();

        return redirect()->route('inventario.traspasos.index')
            ->with('success', 'Traspaso eliminado correctamente.');
    }

    public function agregarLinea(GuardarTraspasoLineaRequest $peticion, Traspaso $traspaso): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($traspaso, $peticion->validated(), $this->existencias),
            'Linea agregada al traspaso.',
        );
    }

    public function eliminarLinea(Traspaso $traspaso, TraspasoLinea $linea): RedirectResponse
    {
        abort_unless($traspaso->estado->esEditable(), 403);
        abort_unless((int) $linea->traspaso_id === (int) $traspaso->id, 404);

        $linea->delete();

        return back()->with('success', 'Linea eliminada.');
    }

    public function enviar(Traspaso $traspaso): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->enviar($traspaso, Auth::user()),
            $traspaso,
            'inventario.traspasos.show',
            'Traspaso enviado: la mercancia salio del almacen de origen.',
        );
    }

    public function recibir(Traspaso $traspaso): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->recibir($traspaso, Auth::user()),
            $traspaso,
            'inventario.traspasos.show',
            'Traspaso recibido: la mercancia entro al almacen de destino.',
        );
    }

    public function cancelar(Traspaso $traspaso): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($traspaso),
            $traspaso,
            'inventario.traspasos.show',
            'Traspaso cancelado.',
        );
    }

    /** @return array<int, float> producto_id => disponible en el almacen de origen */
    private function disponiblesEnOrigen(Traspaso $traspaso): array
    {
        $disponibles = [];

        foreach ($traspaso->lineas as $linea) {
            $disponibles[(int) $linea->producto_id] = $this->existencias
                ->disponible((int) $linea->producto_id, (int) $traspaso->almacen_origen_id);
        }

        return $disponibles;
    }
}
