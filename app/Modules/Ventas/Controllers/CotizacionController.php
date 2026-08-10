<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Enums\EstadoCotizacion;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\CotizacionLinea;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Requests\GuardarCotizacionRequest;
use App\Modules\Ventas\Requests\GuardarLineaVentaRequest;
use App\Modules\Ventas\Services\ServicioCotizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/** Cotizaciones: la oferta con vigencia que arranca la venta. */
class CotizacionController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioCotizacion $servicio) {}

    public function index(Request $peticion): View
    {
        $cotizaciones = Cotizacion::query()
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

        return view('ventas::paginas.cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoCotizacion::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $cotizacion = new Cotizacion([
            'cliente_id' => $peticion->integer('cliente_id') ?: null,
            'fecha' => now()->toDateString(),
            'vigencia' => now()->addDays(15)->toDateString(),
            'moneda' => 'MXN',
        ]);

        return view('ventas::paginas.cotizaciones.create', $this->datosDelFormulario($cotizacion));
    }

    public function store(GuardarCotizacionRequest $peticion): RedirectResponse
    {
        $cotizacion = Cotizacion::create($peticion->validated());

        return redirect()->route('ventas.cotizaciones.show', $cotizacion)
            ->with('success', "Cotizacion {$cotizacion->numero_cotizacion} creada. Agrega sus lineas.");
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['cliente', 'listaPrecio', 'lineas.producto', 'pedidos']);

        return view('ventas::paginas.cotizaciones.show', [
            'cotizacion' => $cotizacion,
            'productos' => Producto::vendibles()->orderBy('nombre')->get(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'impuestos' => $this->impuestos(),
            'bitacora' => $this->bitacoraDe($cotizacion),
        ]);
    }

    public function edit(Cotizacion $cotizacion): View
    {
        abort_unless($cotizacion->estado->esEditable(), 403);

        return view('ventas::paginas.cotizaciones.edit', $this->datosDelFormulario($cotizacion));
    }

    public function update(GuardarCotizacionRequest $peticion, Cotizacion $cotizacion): RedirectResponse
    {
        abort_unless($cotizacion->estado->esEditable(), 403);

        $cotizacion->update($peticion->validated());

        return redirect()->route('ventas.cotizaciones.show', $cotizacion)
            ->with('success', 'Cotizacion actualizada correctamente.');
    }

    public function destroy(Cotizacion $cotizacion): RedirectResponse
    {
        abort_unless($cotizacion->estado->esEditable(), 403);

        $cotizacion->delete();

        return redirect()->route('ventas.cotizaciones.index')
            ->with('success', 'Cotizacion eliminada correctamente.');
    }

    public function agregarLinea(GuardarLineaVentaRequest $peticion, Cotizacion $cotizacion): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($cotizacion, $peticion->validated()),
            'Linea agregada a la cotizacion.',
        );
    }

    public function eliminarLinea(Cotizacion $cotizacion, CotizacionLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->cotizacion_id === (int) $cotizacion->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($cotizacion, $linea),
            'Linea eliminada.',
        );
    }

    public function enviar(Cotizacion $cotizacion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->enviar($cotizacion),
            $cotizacion,
            'ventas.cotizaciones.show',
            'Cotizacion enviada al cliente.',
        );
    }

    /** Aceptar y rechazar son la misma decision, y por eso una sola ruta. */
    public function responder(Request $peticion, Cotizacion $cotizacion): RedirectResponse
    {
        $peticion->validate(['decision' => ['required', 'in:aceptar,rechazar']]);

        $aceptada = $peticion->input('decision') === 'aceptar';

        return $this->ejecutarAccion(
            fn () => $this->servicio->responder($cotizacion, $aceptada),
            $cotizacion,
            'ventas.cotizaciones.show',
            $aceptada ? 'Cotizacion aceptada por el cliente.' : 'Cotizacion rechazada.',
        );
    }

    /** Convierte en pedido y lleva al pedido nuevo: ahi sigue el trabajo. */
    public function convertir(Request $peticion, Cotizacion $cotizacion): RedirectResponse
    {
        $peticion->validate(['almacen_id' => ['nullable', 'integer', 'exists:almacenes,id']]);

        try {
            $pedido = $this->servicio->convertirEnPedido(
                $cotizacion,
                $peticion->integer('almacen_id') ?: null,
            );
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('ventas.pedidos.show', $pedido)
            ->with('success', "Se genero el pedido {$pedido->numero_pedido}. Confirmalo para apartar la existencia.");
    }

    public function cancelar(Cotizacion $cotizacion): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($cotizacion),
            $cotizacion,
            'ventas.cotizaciones.show',
            'Cotizacion cancelada.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Cotizacion $cotizacion): array
    {
        return [
            'cotizacion' => $cotizacion,
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
