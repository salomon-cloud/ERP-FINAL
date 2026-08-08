<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoNominaCorrida;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Requests\AplicarNominaCorridaRequest;
use App\Modules\RH\Requests\GuardarNominaCorridaRequest;
use App\Modules\RH\Services\ServicioCorridaNomina;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * La corrida de nomina, que es un documento con ciclo de vida.
 *
 * Ninguna transicion se decide aqui: procesar, aplicar y cancelar viven en
 * ServicioCorridaNomina. El controlador solo traduce la peticion, atrapa el
 * error de negocio y lo devuelve como mensaje.
 */
class NominaCorridaController extends Controller
{
    public function __construct(private readonly ServicioCorridaNomina $servicio) {}

    public function index(Request $peticion): View
    {
        $corridas = NominaCorrida::query()
            ->with('periodo')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('periodo_id'),
                fn ($consulta) => $consulta->where('periodo_id', $peticion->integer('periodo_id')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('rh::paginas.nomina-corridas.index', [
            'corridas' => $corridas,
            'periodos' => NominaPeriodo::query()->orderByDesc('fecha_inicio')->get(),
            'estados' => OpcionesEnum::de(EstadoNominaCorrida::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.nomina-corridas.create', [
            'periodos' => NominaPeriodo::abiertos()->orderByDesc('fecha_inicio')->get(),
        ]);
    }

    public function store(GuardarNominaCorridaRequest $peticion): RedirectResponse
    {
        $datos = $peticion->validated();
        $periodo = NominaPeriodo::findOrFail($datos['periodo_id']);

        try {
            $corrida = $this->servicio->crear($periodo, $datos['organizacion_id'] ?? null);
        } catch (RuntimeException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        }

        return redirect()->route('rh.nomina-corridas.show', $corrida)
            ->with('success', "Corrida {$corrida->numero_corrida} creada en borrador.");
    }

    public function show(NominaCorrida $nominaCorrida): View
    {
        $nominaCorrida->load(['periodo', 'procesadaPor', 'aprobadaPor']);

        return view('rh::paginas.nomina-corridas.show', [
            'corrida' => $nominaCorrida,
            'recibos' => $nominaCorrida->recibos()->with('empleado')->paginate(25),
        ]);
    }

    public function destroy(NominaCorrida $nominaCorrida): RedirectResponse
    {
        try {
            $this->servicio->cancelar($nominaCorrida);
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('rh.nomina-corridas.index')
            ->with('success', "Corrida {$nominaCorrida->numero_corrida} cancelada.");
    }

    /** Arma los recibos del periodo. Se puede repetir mientras siga en borrador. */
    public function procesar(Request $peticion, NominaCorrida $nominaCorrida): RedirectResponse
    {
        try {
            $this->servicio->procesar($nominaCorrida, $peticion->user());
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('rh.nomina-corridas.show', $nominaCorrida)
            ->with('success', "Corrida procesada: {$nominaCorrida->total_empleados} recibos generados.");
    }

    /** Da la corrida por definitiva, con bloqueo optimista. */
    public function aplicar(AplicarNominaCorridaRequest $peticion, NominaCorrida $nominaCorrida): RedirectResponse
    {
        try {
            $this->servicio->aplicar($nominaCorrida, $peticion->user(), (int) $peticion->validated()['version_fila']);
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('rh.nomina-corridas.show', $nominaCorrida)
            ->with('success', "Corrida {$nominaCorrida->numero_corrida} aplicada.");
    }
}
