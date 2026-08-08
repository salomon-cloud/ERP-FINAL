<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\Empleado;
use Illuminate\View\View;

/**
 * El organigrama, de solo lectura.
 *
 * Se arma con las dos jerarquias que el esquema ya tiene: `departamentos.padre_id`
 * para la estructura y `empleados.jefe_id` para las personas (PLANNING 5.8).
 *
 * Se cargan de golpe los empleados activos y sus departamentos, y el arbol se
 * construye en memoria: son dos consultas en vez de una por rama.
 */
class OrganigramaController extends Controller
{
    public function __invoke(): View
    {
        $departamentos = Departamento::activos()
            ->with('jefe')
            ->orderBy('nombre')
            ->get();

        $empleados = Empleado::activos()
            ->with('puesto')
            ->orderBy('nombre')
            ->get();

        return view('rh::paginas.organigrama.index', [
            'departamentos' => $departamentos,
            // Departamentos colgados de su padre, y empleados de su jefe.
            'raicesDepartamento' => $departamentos->whereNull('padre_id'),
            'departamentosPorPadre' => $departamentos->whereNotNull('padre_id')->groupBy('padre_id'),
            'empleadosPorDepartamento' => $empleados->groupBy('departamento_id'),
            'sinJefe' => $empleados->whereNull('jefe_id'),
            'empleadosPorJefe' => $empleados->whereNotNull('jefe_id')->groupBy('jefe_id'),
        ]);
    }
}
