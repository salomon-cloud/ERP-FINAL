<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoNominaPeriodo;
use App\Modules\RH\Enums\FrecuenciaPago;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Requests\GuardarNominaPeriodoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaPeriodoController extends Controller
{
    public function index(Request $peticion): View
    {
        $periodos = NominaPeriodo::query()
            ->withCount('corridas')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderByDesc('fecha_inicio')
            ->paginate(10)
            ->withQueryString();

        return view('rh::paginas.nomina-periodos.index', [
            'periodos' => $periodos,
            'estados' => OpcionesEnum::de(EstadoNominaPeriodo::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.nomina-periodos.create', $this->datosDelFormulario(new NominaPeriodo));
    }

    public function store(GuardarNominaPeriodoRequest $peticion): RedirectResponse
    {
        NominaPeriodo::create($peticion->validated());

        return redirect()->route('rh.nomina-periodos.index')
            ->with('success', 'Periodo registrado correctamente.');
    }

    public function show(NominaPeriodo $nominaPeriodo): View
    {
        $nominaPeriodo->load('corridas');

        return view('rh::paginas.nomina-periodos.show', ['periodo' => $nominaPeriodo]);
    }

    public function edit(NominaPeriodo $nominaPeriodo): View
    {
        return view('rh::paginas.nomina-periodos.edit', $this->datosDelFormulario($nominaPeriodo));
    }

    public function update(GuardarNominaPeriodoRequest $peticion, NominaPeriodo $nominaPeriodo): RedirectResponse
    {
        $nominaPeriodo->update($peticion->validated());

        return redirect()->route('rh.nomina-periodos.index')
            ->with('success', 'Periodo actualizado correctamente.');
    }

    public function destroy(NominaPeriodo $nominaPeriodo): RedirectResponse
    {
        $nominaPeriodo->delete();

        return redirect()->route('rh.nomina-periodos.index')
            ->with('success', 'Periodo eliminado correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(NominaPeriodo $periodo): array
    {
        return [
            'periodo' => $periodo,
            'frecuencias' => OpcionesEnum::de(FrecuenciaPago::class),
            'estados' => OpcionesEnum::de(EstadoNominaPeriodo::class),
        ];
    }
}
