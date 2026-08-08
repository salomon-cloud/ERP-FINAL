<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\FrecuenciaPago;
use App\Modules\RH\Enums\GeneroEmpleado;
use App\Modules\RH\Enums\TipoContrato;
use App\Modules\RH\Models\Departamento;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Puesto;
use App\Modules\RH\Requests\GuardarEmpleadoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * El expediente del empleado: el catalogo maestro de personas del ERP.
 */
class EmpleadoController extends Controller
{
    public function index(Request $peticion): View
    {
        $empleados = Empleado::query()
            ->with(['departamento', 'puesto', 'jefe'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('departamento_id'),
                fn ($consulta) => $consulta->where('departamento_id', $peticion->integer('departamento_id')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('rh::catalogos.empleados.index', [
            'empleados' => $empleados,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::catalogos.empleados.create', $this->datosDelFormulario(new Empleado));
    }

    public function store(GuardarEmpleadoRequest $peticion): RedirectResponse
    {
        Empleado::create($this->conFotografia($peticion));

        return redirect()->route('rh.empleados.index')
            ->with('success', 'Empleado registrado correctamente.');
    }

    public function show(Empleado $empleado): View
    {
        $empleado->load(['departamento', 'puesto', 'jefe', 'subordinados', 'contratos', 'documentos']);

        return view('rh::catalogos.empleados.show', ['empleado' => $empleado]);
    }

    public function edit(Empleado $empleado): View
    {
        return view('rh::catalogos.empleados.edit', $this->datosDelFormulario($empleado));
    }

    public function update(GuardarEmpleadoRequest $peticion, Empleado $empleado): RedirectResponse
    {
        $empleado->update($this->conFotografia($peticion));

        return redirect()->route('rh.empleados.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Empleado $empleado): RedirectResponse
    {
        $empleado->delete();

        return redirect()->route('rh.empleados.index')
            ->with('success', 'Empleado dado de baja correctamente.');
    }

    /**
     * Los datos validados, con la fotografia guardada si venia uno nuevo.
     *
     * Sin archivo se quita la llave del arreglo, para que editar sin volver a
     * subir la foto no borre la que ya estaba.
     *
     * @return array<string, mixed>
     */
    private function conFotografia(GuardarEmpleadoRequest $peticion): array
    {
        $datos = $peticion->validated();

        if ($peticion->hasFile('fotografia')) {
            $datos['fotografia'] = $peticion->file('fotografia')->store('rh/empleados', 'public');
        } else {
            unset($datos['fotografia']);
        }

        return $datos;
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Empleado $empleado): array
    {
        return [
            'empleado' => $empleado,
            'departamentos' => Departamento::activos()->orderBy('nombre')->get(),
            'puestos' => Puesto::activos()->with('departamento')->orderBy('nombre')->get(),
            // Nadie puede ser su propio jefe.
            'jefes' => Empleado::activos()->whereKeyNot($empleado->getKey())->orderBy('nombre')->get(),
            'generos' => OpcionesEnum::de(GeneroEmpleado::class),
            'tiposContrato' => OpcionesEnum::de(TipoContrato::class),
            'frecuencias' => OpcionesEnum::de(FrecuenciaPago::class),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }
}
