<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Permiso;
use App\Services\ReporteExcelService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteExcelService $excel)
    {
    }
    public function index()
    {
        return view('reportes.index');
    }

    public function empleados(Request $request)
    {
        $items = Empleado::with(['departamento', 'puesto'])
            ->when($request->departamento_id, fn ($q, $id) => $q->where('departamento_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();

        return view('reportes.empleados', ['items' => $items, 'departamentos' => Departamento::all()]);
    }

    public function nominas(Request $request)
    {
        $query = Nomina::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->desde, fn ($q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha));
        $totales = [
            'total_pagar' => (clone $query)->sum('total_pagar'),
            'sueldo_base' => (clone $query)->sum('sueldo_base'),
            'bonos' => (clone $query)->sum('bonos'),
            'horas_extra' => (clone $query)->sum('horas_extra'),
            'deducciones' => (clone $query)->sum('deducciones'),
            'isr' => (clone $query)->sum('isr'),
            'imss' => (clone $query)->sum('imss'),
        ];
        $items = $query->latest()->paginate(15)->withQueryString();

        if ($request->boolean('exportar')) {
            $filtros = trim(implode(' / ', array_filter([
                $request->empleado_id ? 'Empleado: '.Empleado::find($request->empleado_id)?->nombre_completo : null,
                $request->estado ? 'Estado: '.$request->estado : null,
                $request->desde ? 'Desde: '.$request->desde : null,
                $request->hasta ? 'Hasta: '.$request->hasta : null,
            ])));

            $filas = $query->get()->map(fn ($n) => [
                $n->empleado->nombre_completo, $n->periodo_pago, $n->fecha_pago->format('d/m/Y'),
                (float) $n->sueldo_base, (float) $n->bonos, (float) $n->horas_extra, (float) $n->deducciones,
                (float) $n->isr, (float) $n->imss, (float) $n->total_pagar, ucfirst($n->estado), $n->metodo_pago_label,
            ]);

            return $this->excel->download('reporte-nominas.xlsx', 'Reporte de nominas', [
                'Empleado', 'Periodo', 'Fecha', 'Sueldo base', 'Bonos', 'Horas extra', 'Deducciones', 'ISR', 'IMSS', 'Total', 'Estado', 'Metodo',
            ], $filas, [3, 4, 5, 6, 7, 8, 9], [
                0 => 'TOTALES', 3 => $totales['sueldo_base'], 4 => $totales['bonos'], 5 => $totales['horas_extra'],
                6 => $totales['deducciones'], 7 => $totales['isr'], 8 => $totales['imss'], 9 => $totales['total_pagar'],
            ], $filtros ?: null);
        }

        return view('reportes.nominas', ['items' => $items, 'empleados' => Empleado::all(), 'totales' => $totales]);
    }

    public function asistencias(Request $request)
    {
        $items = Asistencia::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->desde, fn ($q, $fecha) => $q->whereDate('fecha', '>=', $fecha))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha', '<=', $fecha))->paginate(15)->withQueryString();

        return view('reportes.asistencias', ['items' => $items, 'empleados' => Empleado::all()]);
    }

    public function permisos(Request $request)
    {
        $items = Permiso::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();

        return view('reportes.permisos', ['items' => $items, 'empleados' => Empleado::all()]);
    }

    public function departamentos(Request $request)
    {
        $items = Departamento::withCount(['empleados', 'puestos'])
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))->paginate(15)->withQueryString();

        return view('reportes.departamentos', compact('items'));
    }

    public function pagosPendientes(Request $request)
    {
        $query = Nomina::with('empleado')->where('estado', 'pendiente')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha));
        $totalPagar = (clone $query)->sum('total_pagar');
        $items = $query->latest()->paginate(15)->withQueryString();

        if ($request->boolean('exportar')) {
            $filas = $query->get()->map(fn ($n) => [
                $n->empleado->nombre_completo, $n->periodo_pago, $n->fecha_pago->format('d/m/Y'),
                (float) $n->total_pagar, $n->metodo_pago_label,
            ]);

            return $this->excel->download('pagos-pendientes.xlsx', 'Reporte de pagos pendientes', [
                'Empleado', 'Periodo', 'Fecha', 'Total', 'Metodo',
            ], $filas, [3], [0 => 'TOTAL PENDIENTE', 3 => (float) $totalPagar]);
        }

        return view('reportes.pagos-pendientes', ['items' => $items, 'empleados' => Empleado::all(), 'total_pagar' => $totalPagar]);
    }

    public function resumenPeriodo(Request $request)
    {
        $rows = Nomina::with('empleado')
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->get();

        if ($request->periodo) {
            $rows = $rows->filter(fn ($n) => $n->fecha_pago->format('Y-m') === $request->periodo);
        }

        $resumen = $rows->groupBy(fn ($n) => $n->fecha_pago->format('Y-m'))
            ->map(function ($grupo, $periodo) {
                return (object) [
                    'periodo' => $periodo,
                    'etiqueta' => $grupo->first()->fecha_pago->format('m-Y'),
                    'total_nominas' => $grupo->count(),
                    'sueldo_base' => $grupo->sum('sueldo_base'),
                    'bonos' => $grupo->sum('bonos'),
                    'horas_extra' => $grupo->sum('horas_extra'),
                    'deducciones' => $grupo->sum('deducciones'),
                    'isr' => $grupo->sum('isr'),
                    'imss' => $grupo->sum('imss'),
                    'total_pagar' => $grupo->sum('total_pagar'),
                    'pagado' => $grupo->where('estado', 'pagada')->sum('total_pagar'),
                    'pendiente' => $grupo->where('estado', 'pendiente')->sum('total_pagar'),
                    'cancelado' => $grupo->where('estado', 'cancelada')->sum('total_pagar'),
                ];
            })->sortKeysDesc();

        $periodos = $resumen->map(fn ($r) => ['periodo' => $r->periodo, 'etiqueta' => $r->etiqueta]);

        if ($request->boolean('exportar')) {
            $totalizado = array_reduce($resumen->values()->all(), function ($acc, $r) {
                foreach ($acc as $k => $v) {
                    $acc[$k] += (float) $r->$k;
                }

                return $acc;
            }, ['total_nominas' => 0, 'sueldo_base' => 0, 'bonos' => 0, 'horas_extra' => 0, 'deducciones' => 0, 'isr' => 0, 'imss' => 0, 'total_pagar' => 0, 'pagado' => 0, 'pendiente' => 0]);

            $filas = $resumen->map(fn ($r) => [
                $r->etiqueta, $r->total_nominas, (float) $r->sueldo_base, (float) ($r->bonos + $r->horas_extra),
                (float) ($r->deducciones + $r->isr + $r->imss), (float) $r->total_pagar, (float) $r->pagado, (float) $r->pendiente,
            ])->values();

            return $this->excel->download('resumen-periodo.xlsx', 'Resumen financiero por periodo', [
                'Periodo', 'Nominas', 'Sueldo', 'Bonos + H.extra', 'Deducciones + ISR + IMSS', 'Total', 'Pagado', 'Pendiente',
            ], $filas, [2, 3, 4, 5, 6, 7], [
                0 => 'TOTALES', 1 => $totalizado['total_nominas'],
                2 => $totalizado['sueldo_base'], 3 => $totalizado['bonos'] + $totalizado['horas_extra'],
                4 => $totalizado['deducciones'] + $totalizado['isr'] + $totalizado['imss'],
                5 => $totalizado['total_pagar'], 6 => $totalizado['pagado'], 7 => $totalizado['pendiente'],
            ], $request->empleado_id ? 'Empleado: '.Empleado::find($request->empleado_id)?->nombre_completo : null);
        }

        return view('reportes.resumen-periodo', ['items' => $resumen, 'periodos' => $periodos, 'empleados' => Empleado::all()]);
    }

    public function costoDepartamento(Request $request)
    {
        $rows = Nomina::with('empleado.departamento')->get();
        if ($request->periodo) {
            $rows = $rows->filter(fn ($n) => $n->fecha_pago->format('Y-m') === $request->periodo);
        }

        $items = $rows->groupBy(fn ($n) => $n->empleado->departamento->id)
            ->map(function ($grupo, $departamentoId) {
                $dep = $grupo->first()->empleado->departamento;

                return (object) [
                    'id' => $dep->id,
                    'nombre' => $dep->nombre,
                    'responsable' => $dep->responsable,
                    'empleados' => $grupo->pluck('empleado_id')->unique()->count(),
                    'sueldo_base' => $grupo->sum('sueldo_base'),
                    'total_pagar' => $grupo->sum('total_pagar'),
                    'pagado' => $grupo->where('estado', 'pagada')->sum('total_pagar'),
                    'pendiente' => $grupo->where('estado', 'pendiente')->sum('total_pagar'),
                ];
            })->sortByDesc('total_pagar');

        $granTotal = (object) ['total_pagar' => $rows->sum('total_pagar'), 'sueldo_base' => $rows->sum('sueldo_base')];
        $periodos = $rows->groupBy(fn ($n) => $n->fecha_pago->format('Y-m'))
            ->map(fn ($grupo, $periodo) => ['periodo' => $periodo, 'etiqueta' => $grupo->first()->fecha_pago->format('m-Y')]);

        if ($request->boolean('exportar')) {
            $filas = $items->map(fn ($d) => [
                $d->nombre, $d->responsable, $d->empleados, (float) $d->sueldo_base,
                (float) $d->total_pagar, (float) $d->pagado, (float) $d->pendiente,
            ])->values();

            return $this->excel->download('costo-departamento.xlsx', 'Costo de nomina por departamento', [
                'Departamento', 'Responsable', 'Empleados', 'Sueldo base', 'Total nomina', 'Pagado', 'Pendiente',
            ], $filas, [3, 4, 5, 6], [
                0 => 'GRAN TOTAL', 3 => (float) $granTotal->sueldo_base, 4 => (float) $granTotal->total_pagar,
            ], $request->periodo ? 'Periodo: '.substr($request->periodo, 3).'-'.substr($request->periodo, 0, 4) : null);
        }

        return view('reportes.costo-departamento', ['items' => $items, 'periodos' => $periodos, 'gran_total' => $granTotal]);
    }

    public function comparativo(Request $request)
    {
        $anio = $request->integer('anio', now()->year);
        $rows = Nomina::whereYear('fecha_pago', $anio)->get();

        $acumulado = 0;
        $prevTotal = null;
        $items = $rows->groupBy(fn ($n) => $n->fecha_pago->format('Y-m'))
            ->map(function ($grupo) {
                return (object) [
                    'periodo' => $grupo->first()->fecha_pago->format('Y-m'),
                    'etiqueta' => $grupo->first()->fecha_pago->format('m-Y'),
                    'total_nominas' => $grupo->count(),
                    'total_pagar' => $grupo->sum('total_pagar'),
                    'pagado' => $grupo->where('estado', 'pagada')->sum('total_pagar'),
                    'pendiente' => $grupo->where('estado', 'pendiente')->sum('total_pagar'),
                ];
            })->sortKeys()
            ->map(function ($row) use (&$acumulado, &$prevTotal) {
                $acumulado += (float) $row->total_pagar;
                $row->acumulado = $acumulado;
                $row->variacion = $prevTotal !== null && $prevTotal > 0
                    ? (($row->total_pagar - $prevTotal) / $prevTotal) * 100 : null;
                $prevTotal = (float) $row->total_pagar;

                return $row;
            })->values();

        $anios = Nomina::pluck('fecha_pago')->map(fn ($f) => $f->year)->unique()->sortDesc()->values();
        if ($anios->isEmpty()) {
            $anios = collect([now()->year]);
        }

        if ($request->boolean('exportar')) {
            $filas = $items->map(fn ($r) => [
                $r->etiqueta, $r->total_nominas, (float) $r->pagado, (float) $r->pendiente,
                (float) $r->total_pagar, (float) $r->acumulado,
                $r->variacion !== null ? number_format($r->variacion, 1).' %' : '',
            ])->values();

            return $this->excel->download('comparativo-'.$anio.'.xlsx', "Comparativo financiero por mes ({$anio})", [
                'Mes', 'Nominas', 'Pagado', 'Pendiente', 'Total del mes', 'Acumulado', 'Variacion %',
            ], $filas, [2, 3, 4, 5]);
        }

        return view('reportes.comparativo', ['items' => $items, 'anio' => $anio, 'anios' => $anios]);
    }
}
