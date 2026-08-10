<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\OrdenCompraLinea;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\GuardarOrdenCompraLineaRequest;
use App\Modules\Compras\Requests\GuardarOrdenCompraRequest;
use App\Modules\Compras\Services\ServicioOrdenCompra;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ordenes de compra.
 *
 * Confirmar y contabilizar mandan `version_fila` en el formulario: es el
 * bloqueo optimista. Si alguien mas movio la orden entre que se abrio la ficha
 * y se pulso el boton, el servicio aborta en vez de autorizar numeros viejos.
 */
class OrdenCompraController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioOrdenCompra $servicio) {}

    public function index(Request $peticion): View
    {
        $ordenes = OrdenCompra::query()
            ->with('proveedor')
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('proveedor_id'),
                fn ($consulta) => $consulta->where('proveedor_id', $peticion->integer('proveedor_id')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras::paginas.ordenes.index', [
            'ordenes' => $ordenes,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoOrdenCompra::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $orden = new OrdenCompra([
            'proveedor_id' => $peticion->integer('proveedor_id') ?: null,
            'fecha' => now()->toDateString(),
            'moneda' => 'MXN',
        ]);

        return view('compras::paginas.ordenes.create', $this->datosDelFormulario($orden));
    }

    public function store(GuardarOrdenCompraRequest $peticion): RedirectResponse
    {
        $orden = OrdenCompra::create($peticion->validated());

        return redirect()->route('compras.ordenes.show', $orden)
            ->with('success', "Orden {$orden->numero_orden} creada. Agrega sus lineas.");
    }

    public function show(OrdenCompra $orden): View
    {
        $orden->load(['proveedor', 'requisicion', 'lineas.producto', 'lineas.almacen', 'recepciones', 'facturas']);

        return view('compras::paginas.ordenes.show', [
            'orden' => $orden,
            'productos' => Producto::comprables()->orderBy('nombre')->get(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'impuestos' => $this->impuestos(),
            'bitacora' => $this->bitacoraDe($orden),
        ]);
    }

    public function edit(OrdenCompra $orden): View
    {
        abort_unless($orden->estado->esEditable(), 403);

        return view('compras::paginas.ordenes.edit', $this->datosDelFormulario($orden));
    }

    public function update(GuardarOrdenCompraRequest $peticion, OrdenCompra $orden): RedirectResponse
    {
        abort_unless($orden->estado->esEditable(), 403);

        $orden->update($peticion->validated());

        return redirect()->route('compras.ordenes.show', $orden)
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(OrdenCompra $orden): RedirectResponse
    {
        abort_unless($orden->estado->esEditable(), 403);

        $orden->delete();

        return redirect()->route('compras.ordenes.index')
            ->with('success', 'Orden eliminada correctamente.');
    }

    public function agregarLinea(GuardarOrdenCompraLineaRequest $peticion, OrdenCompra $orden): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($orden, $peticion->validated()),
            'Linea agregada a la orden.',
        );
    }

    public function eliminarLinea(OrdenCompra $orden, OrdenCompraLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->orden_compra_id === (int) $orden->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($orden, $linea),
            'Linea eliminada.',
        );
    }

    public function enviar(OrdenCompra $orden): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->enviar($orden),
            $orden,
            'compras.ordenes.show',
            'Orden marcada como enviada al proveedor.',
        );
    }

    public function confirmar(Request $peticion, OrdenCompra $orden): RedirectResponse
    {
        $peticion->validate(['version_fila' => ['required', 'integer']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->confirmar($orden, $peticion->integer('version_fila')),
            $orden,
            'compras.ordenes.show',
            'Orden confirmada: ya se puede recibir mercancia contra ella.',
        );
    }

    public function cancelar(OrdenCompra $orden): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($orden),
            $orden,
            'compras.ordenes.show',
            'Orden cancelada.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(OrdenCompra $orden): array
    {
        return [
            'orden' => $orden,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
        ];
    }

    /**
     * Los impuestos de Finanzas, con el Query Builder porque ese modulo
     * todavia no publica su modelo.
     */
    private function impuestos()
    {
        return DB::table('impuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tasa']);
    }
}
