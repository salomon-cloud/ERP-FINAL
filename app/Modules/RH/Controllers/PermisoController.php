<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Enums\TipoPermiso;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Permiso;
use App\Modules\RH\Requests\GuardarPermisoRequest;
use App\Modules\RH\Requests\RevisarPermisoRequest;
use App\Modules\RH\Services\ServicioAprobacionPermisos;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Solicitudes de permiso, vacaciones e incapacidad.
 *
 * La decision (aprobar o rechazar) no se toma aqui: se delega en
 * ServicioAprobacionPermisos, que ademas marca los dias en asistencia y deja el
 * rastro de quien reviso.
 */
class PermisoController extends Controller
{
    public function __construct(private readonly ServicioAprobacionPermisos $servicio) {}

    public function index(Request $peticion): View
    {
        $permisos = Permiso::query()
            ->with(['empleado', 'revisadoPor'])
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('tipo'),
                fn ($consulta) => $consulta->where('tipo', $peticion->input('tipo')))
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('rh::paginas.permisos.index', [
            'permisos' => $permisos,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoPermiso::class),
            'tipos' => OpcionesEnum::de(TipoPermiso::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.permisos.create', $this->datosDelFormulario(new Permiso));
    }

    public function store(GuardarPermisoRequest $peticion): RedirectResponse
    {
        Permiso::create($peticion->validated());

        return redirect()->route('rh.permisos.index')
            ->with('success', 'Solicitud registrada correctamente.');
    }

    public function show(Permiso $permiso): View
    {
        $permiso->load(['empleado', 'revisadoPor']);

        return view('rh::paginas.permisos.show', ['permiso' => $permiso]);
    }

    public function edit(Permiso $permiso): View
    {
        return view('rh::paginas.permisos.edit', $this->datosDelFormulario($permiso));
    }

    public function update(GuardarPermisoRequest $peticion, Permiso $permiso): RedirectResponse
    {
        $permiso->update($peticion->validated());

        return redirect()->route('rh.permisos.index')
            ->with('success', 'Solicitud actualizada correctamente.');
    }

    public function destroy(Permiso $permiso): RedirectResponse
    {
        $permiso->delete();

        return redirect()->route('rh.permisos.index')
            ->with('success', 'Solicitud eliminada correctamente.');
    }

    /**
     * Aprueba o rechaza. Una sola accion para las dos decisiones porque la
     * peticion ya valido cual de las dos es.
     */
    public function revisar(RevisarPermisoRequest $peticion, Permiso $permiso): RedirectResponse
    {
        $decision = EstadoPermiso::from($peticion->validated()['estado']);
        $comentario = $peticion->validated()['comentario_revision'] ?? null;

        try {
            $decision === EstadoPermiso::Aprobado
                ? $this->servicio->aprobar($permiso, $peticion->user(), $comentario)
                : $this->servicio->rechazar($permiso, $peticion->user(), $comentario);
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('rh.permisos.index')
            ->with('success', 'Solicitud '.$decision->label().' correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Permiso $permiso): array
    {
        return [
            'permiso' => $permiso,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'tipos' => OpcionesEnum::de(TipoPermiso::class),
        ];
    }
}
