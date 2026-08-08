<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Requests\GuardarAsistenciaRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function index(Request $peticion): View
    {
        $asistencias = Asistencia::query()
            ->with('empleado')
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        return view('rh::paginas.asistencias.index', [
            'asistencias' => $asistencias,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoAsistencia::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.asistencias.create', $this->datosDelFormulario(new Asistencia));
    }

    public function store(GuardarAsistenciaRequest $peticion): RedirectResponse
    {
        Asistencia::create($peticion->validated());

        return redirect()->route('rh.asistencias.index')
            ->with('success', 'Asistencia registrada correctamente.');
    }

    public function edit(Asistencia $asistencia): View
    {
        return view('rh::paginas.asistencias.edit', $this->datosDelFormulario($asistencia));
    }

    public function update(GuardarAsistenciaRequest $peticion, Asistencia $asistencia): RedirectResponse
    {
        $asistencia->update($peticion->validated());

        return redirect()->route('rh.asistencias.index')
            ->with('success', 'Asistencia actualizada correctamente.');
    }

    public function destroy(Asistencia $asistencia): RedirectResponse
    {
        $asistencia->delete();

        return redirect()->route('rh.asistencias.index')
            ->with('success', 'Asistencia eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Asistencia $asistencia): array
    {
        return [
            'asistencia' => $asistencia,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoAsistencia::class),
        ];
    }
}
