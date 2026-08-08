<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoEvaluacionDesempeno;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\EvaluacionDesempeno;
use App\Modules\RH\Requests\GuardarEvaluacionDesempenoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluacionDesempenoController extends Controller
{
    public function index(Request $peticion): View
    {
        $evaluaciones = EvaluacionDesempeno::query()
            ->with(['empleado', 'evaluador'])
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('periodo_evaluado'),
                fn ($consulta) => $consulta->delPeriodo($peticion->string('periodo_evaluado')->toString()))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderByDesc('periodo_evaluado')
            ->paginate(15)
            ->withQueryString();

        return view('rh::paginas.evaluaciones.index', [
            'evaluaciones' => $evaluaciones,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoEvaluacionDesempeno::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.evaluaciones.create', $this->datosDelFormulario(new EvaluacionDesempeno));
    }

    public function store(GuardarEvaluacionDesempenoRequest $peticion): RedirectResponse
    {
        EvaluacionDesempeno::create($peticion->validated());

        return redirect()->route('rh.evaluaciones.index')
            ->with('success', 'Evaluacion registrada correctamente.');
    }

    public function show(EvaluacionDesempeno $evaluacion): View
    {
        $evaluacion->load(['empleado', 'evaluador']);

        return view('rh::paginas.evaluaciones.show', ['evaluacion' => $evaluacion]);
    }

    public function edit(EvaluacionDesempeno $evaluacion): View
    {
        return view('rh::paginas.evaluaciones.edit', $this->datosDelFormulario($evaluacion));
    }

    public function update(GuardarEvaluacionDesempenoRequest $peticion, EvaluacionDesempeno $evaluacion): RedirectResponse
    {
        $evaluacion->update($peticion->validated());

        return redirect()->route('rh.evaluaciones.index')
            ->with('success', 'Evaluacion actualizada correctamente.');
    }

    public function destroy(EvaluacionDesempeno $evaluacion): RedirectResponse
    {
        $evaluacion->delete();

        return redirect()->route('rh.evaluaciones.index')
            ->with('success', 'Evaluacion eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(EvaluacionDesempeno $evaluacion): array
    {
        return [
            'evaluacion' => $evaluacion,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoEvaluacionDesempeno::class),
        ];
    }
}
