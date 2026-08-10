<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Enums\EstadoRecepcion;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\Compras\Models\RecepcionLinea;
use App\Modules\Compras\Requests\GuardarRecepcionLineaRequest;
use App\Modules\Compras\Requests\GuardarRecepcionRequest;
use App\Modules\Compras\Services\ServicioRecepcion;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Recepciones: el documento que convierte una orden en inventario.
 *
 * Al crearla desde una orden, la ficha ya trae los renglones pendientes con su
 * cantidad sugerida, para que recibir sea confirmar y no capturar de nuevo.
 */
class RecepcionController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioRecepcion $servicio) {}

    public function index(Request $peticion): View
    {
        $recepciones = Recepcion::query()
            ->with(['ordenCompra.proveedor', 'almacen', 'recibidoPor'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras::paginas.recepciones.index', [
            'recepciones' => $recepciones,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'estados' => OpcionesEnum::de(EstadoRecepcion::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $recepcion = new Recepcion([
            'orden_compra_id' => $peticion->integer('orden_compra_id') ?: null,
            'fecha' => now()->toDateString(),
        ]);

        return view('compras::paginas.recepciones.create', [
            'recepcion' => $recepcion,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            // Solo las ordenes que de verdad admiten recepcion; ofrecer las
            // demas seria invitar a un error que el servicio va a rechazar.
            'ordenes' => OrdenCompra::query()
                ->with('proveedor')
                ->whereIn('estado', [
                    EstadoOrdenCompra::Confirmada->value,
                    EstadoOrdenCompra::RecibidaParcial->value,
                ])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(GuardarRecepcionRequest $peticion): RedirectResponse
    {
        $recepcion = Recepcion::create($peticion->validated() + ['recibido_por' => Auth::id()]);

        return redirect()->route('compras.recepciones.show', $recepcion)
            ->with('success', "Recepcion {$recepcion->numero_recepcion} creada. Captura lo que llego.");
    }

    public function show(Recepcion $recepcion): View
    {
        $recepcion->load([
            'ordenCompra.proveedor', 'ordenCompra.lineas.producto',
            'almacen.ubicaciones', 'lineas.producto', 'lineas.ubicacion', 'lineas.lote', 'recibidoPor',
        ]);

        return view('compras::paginas.recepciones.show', [
            'recepcion' => $recepcion,
            // Los renglones que todavia tienen algo pendiente.
            'pendientes' => $recepcion->ordenCompra->lineas
                ->filter(fn ($linea) => $linea->cantidad_pendiente > 0),
            'ubicaciones' => Ubicacion::surtibles()
                ->where('almacen_id', $recepcion->almacen_id)
                ->orderBy('codigo')
                ->get(),
            'lotes' => Lote::activos()
                ->whereIn('producto_id', $recepcion->ordenCompra->lineas->pluck('producto_id')->filter())
                ->orderBy('numero_lote')
                ->get(),
            'bitacora' => $this->bitacoraDe($recepcion),
        ]);
    }

    public function agregarLinea(GuardarRecepcionLineaRequest $peticion, Recepcion $recepcion): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($recepcion, $peticion->validated()),
            'Renglon agregado a la recepcion.',
        );
    }

    public function eliminarLinea(Recepcion $recepcion, RecepcionLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->recepcion_id === (int) $recepcion->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($recepcion, $linea),
            'Renglon eliminado.',
        );
    }

    public function aplicar(Recepcion $recepcion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->aplicar($recepcion, Auth::user()),
            $recepcion,
            'compras.recepciones.show',
            'Recepcion aplicada: la mercancia ya esta en el inventario.',
        );
    }

    public function cancelar(Recepcion $recepcion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($recepcion),
            $recepcion,
            'compras.recepciones.show',
            'Recepcion cancelada. Si ya estaba aplicada, sus movimientos se revirtieron.',
        );
    }
}
