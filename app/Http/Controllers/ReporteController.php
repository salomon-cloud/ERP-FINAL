<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Permiso;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
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
            $rows = $query->get()->map(fn ($n) => [
                $n->empleado->nombre_completo, $n->periodo_pago, $n->fecha_pago->format('d/m/Y'),
                $n->sueldo_base, $n->bonos, $n->horas_extra, $n->deducciones, $n->isr, $n->imss,
                $n->total_pagar, $n->estado, $n->metodo_pago_label,
            ]);

            return $this->downloadCsv('reporte-nominas.csv', [
                'Empleado', 'Periodo', 'Fecha', 'Sueldo base', 'Bonos', 'Horas extra', 'Deducciones', 'ISR', 'IMSS', 'Total', 'Estado', 'Metodo',
            ], $rows);
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
            $rows = $query->get()->map(fn ($n) => [
                $n->empleado->nombre_completo, $n->periodo_pago, $n->fecha_pago->format('d/m/Y'),
                $n->total_pagar, $n->metodo_pago_label,
            ]);

            return $this->downloadCsv('pagos-pendientes.csv', ['Empleado', 'Periodo', 'Fecha', 'Total', 'Metodo'], $rows);
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
            $rows = $resumen->map(fn ($r) => [
                $r->etiqueta, $r->total_nominas, $r->sueldo_base, $r->bonos + $r->horas_extra,
                $r->deducciones + $r->isr + $r->imss, $r->total_pagar, $r->pagado, $r->pendiente,
            ])->values();

            return $this->downloadCsv('resumen-periodo.csv', [
                'Periodo', 'Nominas', 'Sueldo', 'Bonos + H.extra', 'Deducciones + ISR + IMSS', 'Total', 'Pagado', 'Pendiente',
            ], $rows);
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
            $rows = $items->map(fn ($d) => [
                $d->nombre, $d->responsable, $d->empleados, $d->sueldo_base, $d->total_pagar, $d->pagado, $d->pendiente,
            ])->values();

            return $this->downloadCsv('costo-departamento.csv', [
                'Departamento', 'Responsable', 'Empleados', 'Sueldo base', 'Total nomina', 'Pagado', 'Pendiente',
            ], $rows);
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
            $rows = $items->map(fn ($r) => [
                $r->etiqueta, $r->total_nominas, $r->pagado, $r->pendiente, $r->total_pagar, $r->acumulado,
                $r->variacion !== null ? number_format($r->variacion, 1) : '',
            ])->values();

            return $this->downloadCsv('comparativo-'.$anio.'.csv', [
                'Mes', 'Nominas', 'Pagado', 'Pendiente', 'Total del mes', 'Acumulado', 'Variacion %',
            ], $rows);
        }

        return view('reportes.comparativo', ['items' => $items, 'anio' => $anio, 'anios' => $anios]);
    }

    protected function downloadCsv(string $filename, array $headers, $rows)
    {
        $content = "\xEF\xBB\xBF".implode(';', $headers)."\n";

        foreach ($rows as $row) {
            $content .= implode(';', array_map(fn ($value) => $this->csvField($value), $row))."\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    protected function csvField($value): string
    {
        $value = (string) ($value ?? '');
        if (str_contains($value, ';') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
