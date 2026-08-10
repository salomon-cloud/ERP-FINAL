<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoDevolucionCompra;
use App\Modules\Compras\Enums\MotivoDevolucionCompra;
use App\Modules\Compras\Models\DevolucionCompra;
use App\Modules\Compras\Models\DevolucionCompraLinea;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\GuardarDevolucionCompraLineaRequest;
use App\Modules\Compras\Requests\GuardarDevolucionCompraRequest;
use App\Modules\Compras\Services\ServicioDevolucionCompra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Devoluciones a proveedor. */
class DevolucionCompraController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioDevolucionCompra $servicio) {}

    public function index(Request $peticion): View
    {
        $devoluciones = DevolucionCompra::query()
            ->with(['proveedor', 'factura'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('motivo'),
                fn ($consulta) => $consulta->where('motivo', $peticion->input('motivo')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras::paginas.devoluciones.index', [
            'devoluciones' => $devoluciones,
            'estados' => OpcionesEnum::de(EstadoDevolucionCompra::class),
            'motivos' => OpcionesEnum::de(MotivoDevolucionCompra::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $devolucion = new DevolucionCompra([
            'factura_proveedor_id' => $peticion->integer('factura_proveedor_id') ?: null,
            'fecha' => now()->toDateString(),
        ]);

        return view('compras::paginas.devoluciones.create', [
            'devolucion' => $devolucion,
            'motivos' => OpcionesEnum::de(MotivoDevolucionCompra::class),
            // Solo se devuelve contra una factura ya contabilizada: es dinero
            // reclamado, no solo mercancia.
            'facturas' => FacturaProveedor::query()
                ->with('proveedor')
                ->whereIn('estado', ['contabilizada', 'pagada_parcial', 'pagada'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(GuardarDevolucionCompraRequest $peticion): RedirectResponse
    {
        $datos = $peticion->validated();
        $factura = FacturaProveedor::findOrFail($datos['factura_proveedor_id']);

        // El proveedor no se captura: es el de la factura, y si se pudiera
        // elegir se podria reclamarle a quien no vendio.
        $devolucion = DevolucionCompra::create($datos + [
            'proveedor_id' => $factura->proveedor_id,
            'organizacion_id' => $factura->organizacion_id,
        ]);

        return redirect()->route('compras.devoluciones.show', $devolucion)
            ->with('success', "Devolucion {$devolucion->numero_devolucion} creada. Elige que se regresa.");
    }

    public function show(DevolucionCompra $devolucion): View
    {
        $devolucion->load([
            'proveedor', 'factura.lineas.producto', 'lineas.producto', 'lineas.facturaLinea',
        ]);

        return view('compras::paginas.devoluciones.show', [
            'devolucion' => $devolucion,
            'lineasFactura' => $devolucion->factura?->lineas ?? collect(),
            'almacen' => $devolucion->almacenDeSalida(),
            'bitacora' => $this->bitacoraDe($devolucion),
        ]);
    }

    public function destroy(DevolucionCompra $devolucion): RedirectResponse
    {
        abort_unless($devolucion->estado->esEditable(), 403);

        $devolucion->delete();

        return redirect()->route('compras.devoluciones.index')
            ->with('success', 'Devolucion eliminada correctamente.');
    }

    public function agregarLinea(GuardarDevolucionCompraLineaRequest $peticion, DevolucionCompra $devolucion): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($devolucion, $peticion->validated()),
            'Renglon agregado a la devolucion.',
        );
    }

    public function eliminarLinea(DevolucionCompra $devolucion, DevolucionCompraLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->devolucion_id === (int) $devolucion->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($devolucion, $linea),
            'Renglon eliminado.',
        );
    }

    public function aplicar(DevolucionCompra $devolucion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->aplicar($devolucion),
            $devolucion,
            'compras.devoluciones.show',
            'Devolucion aplicada: la mercancia salio del almacen.',
        );
    }

    public function cancelar(DevolucionCompra $devolucion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($devolucion),
            $devolucion,
            'compras.devoluciones.show',
            'Devolucion cancelada.',
        );
    }
}
