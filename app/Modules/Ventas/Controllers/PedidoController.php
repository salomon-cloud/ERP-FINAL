<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Services\ServicioExistencias;
use App\Modules\Ventas\Enums\EstadoPedido;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Models\Pedido;
use App\Modules\Ventas\Models\PedidoLinea;
use App\Modules\Ventas\Requests\GuardarLineaVentaRequest;
use App\Modules\Ventas\Requests\GuardarPedidoRequest;
use App\Modules\Ventas\Requests\SurtirPedidoRequest;
use App\Modules\Ventas\Services\ServicioFactura;
use App\Modules\Ventas\Services\ServicioPedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/**
 * Pedidos: confirmar aparta existencia, surtir la saca del almacen.
 *
 * La ficha muestra el disponible de cada linea ANTES de confirmar, para que la
 * falta de existencia se vea y se resuelva en vez de aparecer como un error al
 * pulsar el boton.
 */
class PedidoController extends Controller
{
    use ControlaDocumentos;

    public function __construct(
        private readonly ServicioPedido $servicio,
        private readonly ServicioFactura $facturas,
        private readonly ServicioExistencias $existencias,
    ) {}

    public function index(Request $peticion): View
    {
        $pedidos = Pedido::query()
            ->with('cliente')
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('cliente_id'),
                fn ($consulta) => $consulta->where('cliente_id', $peticion->integer('cliente_id')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::paginas.pedidos.index', [
            'pedidos' => $pedidos,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoPedido::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $pedido = new Pedido([
            'cliente_id' => $peticion->integer('cliente_id') ?: null,
            'fecha' => now()->toDateString(),
            'moneda' => 'MXN',
        ]);

        return view('ventas::paginas.pedidos.create', $this->datosDelFormulario($pedido));
    }

    public function store(GuardarPedidoRequest $peticion): RedirectResponse
    {
        $pedido = Pedido::create($peticion->validated());

        return redirect()->route('ventas.pedidos.show', $pedido)
            ->with('success', "Pedido {$pedido->numero_pedido} creado. Agrega sus lineas.");
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['cliente', 'cotizacion', 'lineas.producto', 'lineas.almacen', 'facturas']);

        return view('ventas::paginas.pedidos.show', [
            'pedido' => $pedido,
            'productos' => Producto::vendibles()->orderBy('nombre')->get(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'impuestos' => $this->impuestos(),
            // El disponible de cada linea, para ver antes de confirmar si el
            // pedido va a caber.
            'disponibles' => $this->disponiblesDeLasLineas($pedido),
            'bitacora' => $this->bitacoraDe($pedido),
        ]);
    }

    public function edit(Pedido $pedido): View
    {
        abort_unless($pedido->estado->esEditable(), 403);

        return view('ventas::paginas.pedidos.edit', $this->datosDelFormulario($pedido));
    }

    public function update(GuardarPedidoRequest $peticion, Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->estado->esEditable(), 403);

        $pedido->update($peticion->validated());

        return redirect()->route('ventas.pedidos.show', $pedido)
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->estado->esEditable(), 403);

        $pedido->delete();

        return redirect()->route('ventas.pedidos.index')
            ->with('success', 'Pedido eliminado correctamente.');
    }

    public function agregarLinea(GuardarLineaVentaRequest $peticion, Pedido $pedido): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($pedido, $peticion->validated()),
            'Linea agregada al pedido.',
        );
    }

    public function eliminarLinea(Pedido $pedido, PedidoLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->pedido_id === (int) $pedido->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($pedido, $linea),
            'Linea eliminada.',
        );
    }

    public function confirmar(Request $peticion, Pedido $pedido): RedirectResponse
    {
        $peticion->validate(['version_fila' => ['required', 'integer']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->confirmar($pedido, $peticion->integer('version_fila')),
            $pedido,
            'ventas.pedidos.show',
            'Pedido confirmado: la existencia quedo apartada para este cliente.',
        );
    }

    public function surtir(SurtirPedidoRequest $peticion, Pedido $pedido): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->surtir($pedido, $peticion->cantidades()),
            $pedido,
            'ventas.pedidos.show',
            'Pedido surtido: la mercancia salio del almacen.',
        );
    }

    /** Genera la factura de lo surtido y lleva a la factura nueva. */
    public function facturar(Pedido $pedido): RedirectResponse
    {
        try {
            $factura = $this->facturas->crearDesdePedido($pedido);
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('ventas.facturas.show', $factura)
            ->with('success', "Se genero la factura {$factura->numero_factura} en borrador. Revisala y emitela.");
    }

    public function cancelar(Pedido $pedido): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($pedido),
            $pedido,
            'ventas.pedidos.show',
            'Pedido cancelado y existencia liberada.',
        );
    }

    /** @return array<int, float> linea_id => disponible en su almacen */
    private function disponiblesDeLasLineas(Pedido $pedido): array
    {
        $disponibles = [];

        foreach ($pedido->lineas as $linea) {
            $disponibles[(int) $linea->id] = $linea->almacen_id !== null
                ? $this->existencias->disponible((int) $linea->producto_id, (int) $linea->almacen_id)
                : 0.0;
        }

        return $disponibles;
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Pedido $pedido): array
    {
        return [
            'pedido' => $pedido,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'listas' => ListaPrecio::orderBy('nombre')->get(),
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
