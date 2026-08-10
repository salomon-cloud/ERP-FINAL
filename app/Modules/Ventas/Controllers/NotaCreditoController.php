<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Ventas\Enums\EstadoNotaCredito;
use App\Modules\Ventas\Enums\MotivoNotaCredito;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Models\NotaCredito;
use App\Modules\Ventas\Models\NotaCreditoLinea;
use App\Modules\Ventas\Requests\GuardarNotaCreditoLineaRequest;
use App\Modules\Ventas\Requests\GuardarNotaCreditoRequest;
use App\Modules\Ventas\Services\ServicioNotaCredito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notas de credito: como se corrige una factura ya emitida. */
class NotaCreditoController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioNotaCredito $servicio) {}

    public function index(Request $peticion): View
    {
        $notas = NotaCredito::query()
            ->with(['cliente', 'factura'])
            ->withCount('lineas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('motivo'),
                fn ($consulta) => $consulta->where('motivo', $peticion->input('motivo')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::paginas.notas-credito.index', [
            'notas' => $notas,
            'estados' => OpcionesEnum::de(EstadoNotaCredito::class),
            'motivos' => OpcionesEnum::de(MotivoNotaCredito::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $nota = new NotaCredito([
            'factura_id' => $peticion->integer('factura_id') ?: null,
            'fecha_emision' => now()->toDateString(),
        ]);

        return view('ventas::paginas.notas-credito.create', [
            'nota' => $nota,
            'motivos' => OpcionesEnum::de(MotivoNotaCredito::class),
            // Solo contra facturas ya emitidas: un borrador se corrige editandolo.
            'facturas' => Factura::query()
                ->with('cliente')
                ->whereIn('estado', ['emitida', 'cobrada_parcial', 'cobrada', 'vencida'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(GuardarNotaCreditoRequest $peticion): RedirectResponse
    {
        $datos = $peticion->validated();
        $factura = Factura::findOrFail($datos['factura_id']);

        // El cliente no se captura: es el de la factura, y si se pudiera elegir
        // se podria acreditar a quien no compro.
        $nota = NotaCredito::create($datos + [
            'cliente_id' => $factura->cliente_id,
            'organizacion_id' => $factura->organizacion_id,
        ]);

        return redirect()->route('ventas.notas-credito.show', $nota)
            ->with('success', "Nota de credito {$nota->numero_nota} creada. Elige que se acredita.");
    }

    public function show(NotaCredito $nota): View
    {
        $nota->load(['cliente', 'factura.lineas.producto', 'lineas.producto', 'lineas.facturaLinea']);

        return view('ventas::paginas.notas-credito.show', [
            'nota' => $nota,
            'lineasFactura' => $nota->factura?->lineas ?? collect(),
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'bitacora' => $this->bitacoraDe($nota),
        ]);
    }

    public function destroy(NotaCredito $nota): RedirectResponse
    {
        abort_unless($nota->estado->esEditable(), 403);

        $nota->delete();

        return redirect()->route('ventas.notas-credito.index')
            ->with('success', 'Nota de credito eliminada correctamente.');
    }

    public function agregarLinea(GuardarNotaCreditoLineaRequest $peticion, NotaCredito $nota): RedirectResponse
    {
        return $this->ejecutarEnSitio(
            fn () => $this->servicio->agregarLinea($nota, $peticion->validated()),
            'Renglon agregado a la nota de credito.',
        );
    }

    public function eliminarLinea(NotaCredito $nota, NotaCreditoLinea $linea): RedirectResponse
    {
        abort_unless((int) $linea->nota_credito_id === (int) $nota->id, 404);

        return $this->ejecutarEnSitio(
            fn () => $this->servicio->eliminarLinea($nota, $linea),
            'Renglon eliminado.',
        );
    }

    public function emitir(Request $peticion, NotaCredito $nota): RedirectResponse
    {
        $peticion->validate(['almacen_id' => ['nullable', 'integer', 'exists:almacenes,id']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->emitir($nota, $peticion->integer('almacen_id') ?: null),
            $nota,
            'ventas.notas-credito.show',
            $nota->motivo->regresaMercancia()
                ? 'Nota emitida: la mercancia regreso al almacen.'
                : 'Nota de credito emitida.',
        );
    }

    public function cancelar(NotaCredito $nota): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($nota),
            $nota,
            'ventas.notas-credito.show',
            'Nota de credito cancelada.',
        );
    }
}
