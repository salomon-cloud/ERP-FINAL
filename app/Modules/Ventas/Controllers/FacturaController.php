<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\FacturaLinea;
use App\Modules\Ventas\Models\Pedido;
use App\Modules\Ventas\Requests\GuardarFacturaLineaRequest;
use App\Modules\Ventas\Requests\GuardarFacturaRequest;
use App\Modules\Ventas\Services\ServicioFactura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Facturas de venta.
 *
 * Emitir es un punto de no retorno: a partir de ahi la factura no se edita, se
 * corrige con una nota de credito. La ficha lo dice y esconde los formularios
 * de captura en cuanto pasa.
 */
class FacturaController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioFactura $servicio) {}

    public function index(Request $peticion): View
    {
        $facturas = Factura::query()
            ->with(['cliente', 'pedido'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('cliente_id'),
                fn ($consulta) => $consulta->where('cliente_id', $peticion->integer('cliente_id')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha_emision', '<=', $peticion->date('hasta')))
            // Solo las que ya vencieron y siguen con saldo: es un filtro
            // derivado, no un estado guardado.
            ->when($peticion->boolean('solo_vencidas'), fn ($consulta) => $consulta
                ->whereIn('estado', ['emitida', 'cobrada_parcial'])
                ->whereDate('fecha_vencimiento', '<', now()->toDateString()))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::paginas.facturas.index', [
            'facturas' => $facturas,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoFactura::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $factura = new Factura([
            'cliente_id' => $peticion->integer('cliente_id') ?: null,
            'fecha_emision' => now()->toDateString(),
            'moneda' => 'MXN',
        ]);

        return view('ventas::paginas.facturas.create', $this->datosDelFormulario($factura));
    }

    public function store(GuardarFacturaRequest $peticion): RedirectResponse
    {
        $factura = Factura::create($peticion->validated());

        return redirect()->route('ventas.facturas.show', $factura)
            ->with('success', "Factura {$factura->numero_factura} creada en borrador.");
    }

    public function show(Factura $factura): View
    {
        $factura->load(['cliente', 'pedido', 'condicionPago', 'lineas.producto', 'cobros', 'notasCredito']);

        return view('ventas::paginas.facturas.show', [
            'factura' => $factura,
            'productos' => Producto::vendibles()->orderBy('nombre')->get(),
            'impuestos' => $this->impuestos(),
            'bitacora' => $this->bitacoraDe($factura),
        ]);
    }

    public function edit(Factura $factura): View
    {
        abort_unless($factura->estado->esEditable(), 403);

        return view('ventas::paginas.facturas.edit', $this->datosDelFormulario($factura));
    }

    public function update(GuardarFacturaRequest $peticion, Factura $factura): RedirectResponse
    {
        abort_unless($factura->estado->esEditable(), 403);

        $factura->update($peticion->validated());

        return redirect()->route('ventas.facturas.show', $factura)
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(Factura $factura): RedirectResponse
    {
        abort_unless($factura->estado->esEditable(), 403);

        $factura->delete();

        return redirect()->route('ventas.facturas.index')
            ->with('success', 'Factura eliminada correctamente.');
    }

    public function agregarLinea(GuardarFacturaLineaRequest $peticion, Factura $factura): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($factura, $peticion->validated()),
            'Linea agregada a la factura.',
        );
    }

    public function eliminarLinea(Factura $factura, FacturaLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->factura_id === (int) $factura->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($factura, $linea),
            'Linea eliminada.',
        );
    }

    public function emitir(Request $peticion, Factura $factura): RedirectResponse
    {
        $peticion->validate(['version_fila' => ['required', 'integer']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->emitir($factura, $peticion->integer('version_fila')),
            $factura,
            'ventas.facturas.show',
            'Factura emitida y contabilizada. Ya se puede cobrar.',
        );
    }

    public function cancelar(Factura $factura): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($factura),
            $factura,
            'ventas.facturas.show',
            'Factura cancelada.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Factura $factura): array
    {
        return [
            'factura' => $factura,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'pedidos' => Pedido::query()
                ->with('cliente')
                ->whereIn('estado', ['surtido', 'facturado_parcial'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'condicionesPago' => Catalogo::grupo('condiciones_pago')->get(),
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
