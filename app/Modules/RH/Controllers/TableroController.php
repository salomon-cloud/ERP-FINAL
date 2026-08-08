<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Contrato;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\Permiso;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * El tablero del modulo. Sustituye a la pagina provisional de Compartido y
 * conserva el nombre de ruta `rh.dashboard`, que es al que apunta la barra
 * lateral.
 *
 * Cada KPI es una consulta real y enlaza a la lista que lo demuestra, tal como
 * exige PLANNING en "Dashboard Philosophy": aqui no se pinta ningun numero que
 * el usuario no pueda auditar dando clic.
 */
class TableroController extends Controller
{
    public function __invoke(): View
    {
        $hoy = Carbon::today();

        return view('rh::dashboard.index', [
            'empleadosActivos' => Empleado::activos()->count(),
            'ausenciasHoy' => Asistencia::query()
                ->whereDate('fecha', $hoy)
                ->where('estado', EstadoAsistencia::Falta)
                ->count(),
            'permisosPendientes' => Permiso::pendientes()->count(),
            'contratosPorVencer' => Contrato::porVencer(30)->count(),

            'solicitudesPendientes' => Permiso::pendientes()
                ->with('empleado')
                ->orderBy('fecha_inicio')
                ->limit(5)
                ->get(),

            'ultimasCorridas' => NominaCorrida::query()
                ->with('periodo')
                ->latest('id')
                ->limit(5)
                ->get(),

            'cumpleanosDelMes' => Empleado::activos()
                ->whereMonth('fecha_nacimiento', $hoy->month)
                ->orderByRaw('DAY(fecha_nacimiento)')
                ->limit(8)
                ->get(),

            'porDepartamento' => Empleado::query()
                ->where('empleados.estado', EstadoActivacion::Activo)
                ->join('departamentos', 'departamentos.id', '=', 'empleados.departamento_id')
                ->selectRaw('departamentos.nombre AS departamento, COUNT(*) AS total')
                ->groupBy('departamentos.nombre')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),

            'estadoPendiente' => EstadoPermiso::Pendiente->value,
            'estadoFalta' => EstadoAsistencia::Falta->value,
            'hoy' => $hoy->toDateString(),
        ]);
    }
}
