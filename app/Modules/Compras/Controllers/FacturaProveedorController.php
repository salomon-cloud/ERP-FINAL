<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\FacturaProveedorLinea;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\GuardarFacturaProveedorLineaRequest;
use App\Modules\Compras\Requests\GuardarFacturaProveedorRequest;
use App\Modules\Compras\Services\ServicioFacturaProveedor;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Facturas de proveedor: la cuenta por pagar y su cotejo de tres vias.
 *
 * La ficha muestra el resultado del cotejo ANTES de contabilizar, para que la
 * diferencia se vea y se resuelva en vez de aparecer como un error al pulsar el
 * boton.
 */
class FacturaProveedorController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioFacturaProveedor $servicio) {}

    public function index(Request $peticion): View
    {
        $facturas = FacturaProveedor::query()
            ->with(['proveedor', 'ordenCompra'])
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

        return view('compras::paginas.facturas.index', [
            'facturas' => $facturas,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoFacturaProveedor::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $factura = new FacturaProveedor([
            'proveedor_id' => $peticion->integer('proveedor_id') ?: null,
            'orden_compra_id' => $peticion->integer('orden_compra_id') ?: null,
            'fecha' => now()->toDateString(),
            'moneda' => 'MXN',
        ]);

        return view('compras::paginas.facturas.create', $this->datosDelFormulario($factura));
    }

    public function store(GuardarFacturaProveedorRequest $peticion): RedirectResponse
    {
        $factura = FacturaProveedor::create($peticion->validated());

        return redirect()->route('compras.facturas.show', $factura)
            ->with('success', "Factura {$factura->numero_factura} creada. Captura o copia sus lineas.");
    }

    public function show(FacturaProveedor $factura): View
    {
        $factura->load([
            'proveedor', 'ordenCompra.lineas.producto', 'recepcion',
            'lineas.producto', 'lineas.ordenCompraLinea', 'pagos', 'devoluciones',
        ]);

        return view('compras::paginas.facturas.show', [
            'factura' => $factura,
            'productos' => Producto::comprables()->orderBy('nombre')->get(),
            'impuestos' => $this->impuestos(),
            // El cotejo se calcula para MOSTRARLO, no solo para bloquear.
            'diferencias' => $this->servicio->cotejarTresVias($factura),
            'bitacora' => $this->bitacoraDe($factura),
        ]);
    }

    public function edit(FacturaProveedor $factura): View
    {
        abort_unless($factura->estado->esEditable(), 403);

        return view('compras::paginas.facturas.edit', $this->datosDelFormulario($factura));
    }

    public function update(GuardarFacturaProveedorRequest $peticion, FacturaProveedor $factura): RedirectResponse
    {
        abort_unless($factura->estado->esEditable(), 403);

        $factura->update($peticion->validated());

        return redirect()->route('compras.facturas.show', $factura)
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(FacturaProveedor $factura): RedirectResponse
    {
        abort_unless($factura->estado->esEditable(), 403);

        $factura->delete();

        return redirect()->route('compras.facturas.index')
            ->with('success', 'Factura eliminada correctamente.');
    }

    public function agregarLinea(GuardarFacturaProveedorLineaRequest $peticion, FacturaProveedor $factura): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($factura, $peticion->validated()),
            'Linea agregada a la factura.',
        );
    }

    public function eliminarLinea(FacturaProveedor $factura, FacturaProveedorLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->factura_proveedor_id === (int) $factura->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($factura, $linea),
            'Linea eliminada.',
        );
    }

    /** Copia a la factura lo que ya se recibio de la orden. */
    public function copiarDeOrden(FacturaProveedor $factura): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->copiarDesdeOrden($factura),
            'Se copiaron a la factura los renglones recibidos de la orden.',
        );
    }

    public function contabilizar(Request $peticion, FacturaProveedor $factura): RedirectResponse
    {
        $peticion->validate(['version_fila' => ['required', 'integer']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->contabilizar($factura, $peticion->integer('version_fila')),
            $factura,
            'compras.facturas.show',
            'Factura contabilizada: ya se puede pagar.',
        );
    }

    public function cancelar(FacturaProveedor $factura): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($factura),
            $factura,
            'compras.facturas.show',
            'Factura cancelada.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(FacturaProveedor $factura): array
    {
        return [
            'factura' => $factura,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'ordenes' => OrdenCompra::query()
                ->with('proveedor')
                ->whereIn('estado', ['recibida_parcial', 'recibida', 'facturada'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'periodos' => DB::table('periodos_fiscales')
                ->whereNull('deleted_at')
                ->where('estado', 'abierto')
                ->orderByDesc('fecha_inicio')
                ->get(['id', 'nombre', 'ejercicio']),
        ];
    }

    private function impuestos()
    {
        return DB::table('impuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tasa']);
    }
}
