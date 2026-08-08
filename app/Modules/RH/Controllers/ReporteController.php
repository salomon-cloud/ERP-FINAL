<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Enums\EstadoContrato;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Contrato;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Nomina;
use App\Modules\RH\Models\Permiso;
use App\Modules\RH\Utils\ExportadorCsv;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los reportes del modulo.
 *
 * Todos siguen el mismo contrato: la misma consulta alimenta la pantalla y el
 * CSV, asi que lo que el usuario exporta es exactamente lo que vio. La bandera
 * `?formato=csv` es lo unico que cambia.
 *
 * Las cifras salen de los documentos tal como estan; aqui no se recalcula nada
 * (PLANNING - "Reporting System": los reportes son vistas derivadas, nunca una
 * segunda copia de la verdad).
 */
class ReporteController extends Controller
{
    public function index(): Renderable
    {
        return view('rh::paginas.reportes.index');
    }

    /** Plantilla: quien esta contratado, donde y con cuanto. */
    public function empleados(Request $peticion): Renderable|StreamedResponse
    {
        $empleados = $this->consultaEmpleados($peticion)->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('empleados',
                ['Numero', 'Nombre', 'Departamento', 'Puesto', 'Contratacion', 'Tipo de contrato', 'Sueldo base', 'Estado'],
                $empleados->map(fn (Empleado $e) => [
                    $e->numero_empleado,
                    $e->nombre_completo,
                    $e->departamento?->nombre,
                    $e->puesto?->nombre,
                    $e->fecha_contratacion?->format('d/m/Y'),
                    $e->tipo_contrato?->label(),
                    (float) $e->sueldo_base,
                    $e->estado->label(),
                ]));
        }

        return view('rh::paginas.reportes.empleados', [
            'empleados' => $empleados,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
            'nomina' => $empleados->sum(fn (Empleado $e) => (float) $e->sueldo_base),
        ]);
    }

    /** Asistencia en un rango, con las horas trabajadas del periodo. */
    public function asistencias(Request $peticion): Renderable|StreamedResponse
    {
        $asistencias = $this->consultaAsistencias($peticion)->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('asistencias',
                ['Fecha', 'Empleado', 'Entrada', 'Salida', 'Horas', 'Estado'],
                $asistencias->map(fn (Asistencia $a) => [
                    $a->fecha->format('d/m/Y'),
                    $a->empleado?->nombre_completo,
                    $a->hora_entrada,
                    $a->hora_salida,
                    (float) $a->horas_trabajadas,
                    $a->estado->label(),
                ]));
        }

        return view('rh::paginas.reportes.asistencias', [
            'asistencias' => $asistencias,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoAsistencia::class),
            'horas' => $asistencias->sum(fn (Asistencia $a) => (float) $a->horas_trabajadas),
            'porEstado' => $asistencias->groupBy(fn (Asistencia $a) => $a->estado->label())->map->count(),
        ]);
    }

    /** Permisos y vacaciones, con los dias que impactan la nomina. */
    public function permisos(Request $peticion): Renderable|StreamedResponse
    {
        $permisos = $this->consultaPermisos($peticion)->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('permisos',
                ['Empleado', 'Tipo', 'Inicio', 'Fin', 'Dias', 'Goce', 'Estado', 'Reviso'],
                $permisos->map(fn (Permiso $p) => [
                    $p->empleado?->nombre_completo,
                    $p->tipo->label(),
                    $p->fecha_inicio->format('d/m/Y'),
                    $p->fecha_fin->format('d/m/Y'),
                    (float) $p->dias,
                    $p->con_goce ? 'Con goce' : 'Sin goce',
                    $p->estado->label(),
                    $p->revisadoPor?->name,
                ]));
        }

        return view('rh::paginas.reportes.permisos', [
            'permisos' => $permisos,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoPermiso::class),
            'diasTotales' => $permisos->sum(fn (Permiso $p) => (float) $p->dias),
            'diasSinGoce' => $permisos->where('con_goce', false)->sum(fn (Permiso $p) => (float) $p->dias),
        ]);
    }

    /** Recibos pagados y por pagar en un rango. */
    public function nomina(Request $peticion): Renderable|StreamedResponse
    {
        $recibos = $this->consultaNomina($peticion)->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('nomina',
                ['Empleado', 'Periodo', 'Corrida', 'Pago', 'Sueldo', 'Bonos', 'Deducciones', 'ISR', 'IMSS', 'Neto', 'Estado'],
                $recibos->map(fn (Nomina $n) => [
                    $n->empleado?->nombre_completo,
                    $n->periodo_pago,
                    $n->corrida?->numero_corrida,
                    $n->fecha_pago->format('d/m/Y'),
                    (float) $n->sueldo_base,
                    (float) $n->bonos,
                    (float) $n->deducciones,
                    (float) $n->isr,
                    (float) $n->imss,
                    (float) $n->total_pagar,
                    $n->estado->label(),
                ]));
        }

        return view('rh::paginas.reportes.nomina', [
            'recibos' => $recibos,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'percepciones' => $recibos->sum(fn (Nomina $n) => (float) $n->sueldo_base + (float) $n->bonos + (float) $n->horas_extra),
            'deducciones' => $recibos->sum(fn (Nomina $n) => (float) $n->deducciones + (float) $n->isr + (float) $n->imss),
            'neto' => $recibos->sum(fn (Nomina $n) => (float) $n->total_pagar),
        ]);
    }

    /** Contratos vigentes que vencen pronto: el reporte que evita una renovacion olvidada. */
    public function contratosPorVencer(Request $peticion): Renderable|StreamedResponse
    {
        $dias = max(1, $peticion->integer('dias', 30));

        $contratos = Contrato::query()
            ->with('empleado')
            ->porVencer($dias)
            ->orderBy('fecha_fin')
            ->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('contratos-por-vencer',
                ['Empleado', 'Numero', 'Tipo', 'Inicio', 'Vence', 'Dias restantes', 'Sueldo'],
                $contratos->map(fn (Contrato $c) => [
                    $c->empleado?->nombre_completo,
                    $c->numero_contrato,
                    $c->tipo_contrato->label(),
                    $c->fecha_inicio->format('d/m/Y'),
                    $c->fecha_fin?->format('d/m/Y'),
                    $c->fecha_fin ? (int) Carbon::today()->diffInDays($c->fecha_fin, false) : null,
                    (float) $c->sueldo,
                ]));
        }

        return view('rh::paginas.reportes.contratos-por-vencer', [
            'contratos' => $contratos,
            'dias' => $dias,
            'estados' => OpcionesEnum::de(EstadoContrato::class),
        ]);
    }

    /** Plantilla por departamento: cuanta gente y cuanto cuesta cada area. */
    public function plantillaPorDepartamento(Request $peticion): Renderable|StreamedResponse
    {
        $filas = Empleado::query()
            ->where('empleados.estado', EstadoActivacion::Activo)
            ->join('departamentos', 'departamentos.id', '=', 'empleados.departamento_id')
            ->selectRaw('departamentos.nombre AS departamento')
            ->selectRaw('COUNT(*) AS empleados')
            ->selectRaw('COALESCE(SUM(empleados.sueldo_base), 0) AS nomina')
            ->selectRaw('COALESCE(AVG(empleados.sueldo_base), 0) AS promedio')
            ->groupBy('departamentos.nombre')
            ->orderByDesc('empleados')
            ->get();

        if ($this->pideCsv($peticion)) {
            return ExportadorCsv::descargar('plantilla-por-departamento',
                ['Departamento', 'Empleados', 'Nomina mensual', 'Sueldo promedio'],
                $filas->map(fn ($f) => [
                    $f->departamento,
                    (int) $f->empleados,
                    (float) $f->nomina,
                    round((float) $f->promedio, 2),
                ]));
        }

        return view('rh::paginas.reportes.plantilla-por-departamento', [
            'filas' => $filas,
            'totalEmpleados' => $filas->sum(fn ($f) => (int) $f->empleados),
            'totalNomina' => $filas->sum(fn ($f) => (float) $f->nomina),
        ]);
    }

    private function pideCsv(Request $peticion): bool
    {
        return $peticion->string('formato')->toString() === 'csv';
    }

    private function consultaEmpleados(Request $peticion): Builder
    {
        return Empleado::query()
            ->with(['departamento', 'puesto'])
            ->when($peticion->filled('departamento_id'),
                fn (Builder $c) => $c->where('departamento_id', $peticion->integer('departamento_id')))
            ->when($peticion->filled('estado'),
                fn (Builder $c) => $c->where('estado', $peticion->input('estado')))
            ->orderBy('nombre');
    }

    private function consultaAsistencias(Request $peticion): Builder
    {
        return Asistencia::query()
            ->with('empleado')
            ->when($peticion->filled('empleado_id'),
                fn (Builder $c) => $c->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn (Builder $c) => $c->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('desde'),
                fn (Builder $c) => $c->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn (Builder $c) => $c->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('fecha');
    }

    private function consultaPermisos(Request $peticion): Builder
    {
        return Permiso::query()
            ->with(['empleado', 'revisadoPor'])
            ->when($peticion->filled('empleado_id'),
                fn (Builder $c) => $c->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn (Builder $c) => $c->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('desde') && $peticion->filled('hasta'),
                fn (Builder $c) => $c->queCruzan(
                    $peticion->date('desde')->toDateString(),
                    $peticion->date('hasta')->toDateString()
                ))
            ->orderByDesc('fecha_inicio');
    }

    private function consultaNomina(Request $peticion): Builder
    {
        return Nomina::query()
            ->with(['empleado', 'corrida'])
            ->when($peticion->filled('empleado_id'),
                fn (Builder $c) => $c->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('estado'),
                fn (Builder $c) => $c->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('desde'),
                fn (Builder $c) => $c->whereDate('fecha_pago', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn (Builder $c) => $c->whereDate('fecha_pago', '<=', $peticion->date('hasta')))
            ->orderByDesc('fecha_pago');
    }
}
