<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoNomina;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Nomina;
use App\Modules\RH\Requests\GuardarNominaRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * El recibo por empleado.
 *
 * Normalmente los genera ServicioCorridaNomina; estas pantallas cubren la
 * captura suelta que SISEN v1 ya permitia y la correccion de un recibo.
 *
 * `total_pagar` nunca se captura: lo deriva ObservadorNomina.
 */
class NominaController extends Controller
{
    public function index(Request $peticion): View
    {
        $nominas = Nomina::query()
            ->with(['empleado', 'corrida'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderByDesc('fecha_pago')
            ->paginate(15)
            ->withQueryString();

        return view('rh::paginas.nominas.index', [
            'nominas' => $nominas,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoNomina::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.nominas.create', $this->datosDelFormulario(new Nomina));
    }

    public function store(GuardarNominaRequest $peticion): RedirectResponse
    {
        Nomina::create($peticion->validated());

        return redirect()->route('rh.nominas.index')
            ->with('success', 'Recibo registrado correctamente.');
    }

    public function show(Nomina $nomina): View
    {
        $nomina->load(['empleado', 'corrida']);

        return view('rh::paginas.nominas.show', ['nomina' => $nomina]);
    }

    public function edit(Nomina $nomina): View
    {
        return view('rh::paginas.nominas.edit', $this->datosDelFormulario($nomina));
    }

    public function update(GuardarNominaRequest $peticion, Nomina $nomina): RedirectResponse
    {
        $nomina->update($peticion->validated());

        return redirect()->route('rh.nominas.index')
            ->with('success', 'Recibo actualizado correctamente.');
    }

    public function destroy(Nomina $nomina): RedirectResponse
    {
        $nomina->delete();

        return redirect()->route('rh.nominas.index')
            ->with('success', 'Recibo eliminado correctamente.');
    }

    /** Marca el recibo como pagado. Equivale al `pagar` de SISEN v1. */
    public function pagar(Nomina $nomina): RedirectResponse
    {
        if ($nomina->estado !== EstadoNomina::Pendiente) {
            return back()->with('error', 'Solo se paga un recibo pendiente.');
        }

        $nomina->update(['estado' => EstadoNomina::Pagada, 'pagada_en' => now()]);
        $nomina->registrarBitacora('pagada', [], ['estado' => EstadoNomina::Pagada->value]);

        return back()->with('success', 'Recibo marcado como pagado.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Nomina $nomina): array
    {
        return [
            'nomina' => $nomina,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoNomina::class),
        ];
    }
}
