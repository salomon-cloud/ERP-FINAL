<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\GuardarProveedorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** El catalogo de proveedores, con su ficha de historial. */
class ProveedorController extends Controller
{
    public function index(Request $peticion): View
    {
        $proveedores = Proveedor::query()
            ->with('condicionPago')
            ->withCount('ordenes')
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('compras::catalogos.proveedores.index', [
            'proveedores' => $proveedores,
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function create(): View
    {
        return view('compras::catalogos.proveedores.create', $this->datosDelFormulario(new Proveedor));
    }

    public function store(GuardarProveedorRequest $peticion): RedirectResponse
    {
        $proveedor = Proveedor::create($peticion->validated());

        return redirect()->route('compras.proveedores.show', $proveedor)
            ->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Proveedor $proveedor): View
    {
        return view('compras::catalogos.proveedores.show', [
            'proveedor' => $proveedor,
            'ordenes' => $proveedor->ordenes()->latest('id')->limit(10)->get(),
            'facturas' => $proveedor->facturas()->latest('id')->limit(10)->get(),
            'pagos' => $proveedor->pagos()->latest('id')->limit(10)->get(),
        ]);
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('compras::catalogos.proveedores.edit', $this->datosDelFormulario($proveedor));
    }

    public function update(GuardarProveedorRequest $peticion, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($peticion->validated());

        return redirect()->route('compras.proveedores.show', $proveedor)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Baja logica, y solo si no tiene historia.
     *
     * Las llaves foraneas de ordenes, facturas y pagos son RESTRICT: un
     * proveedor con documentos no se borra ni debe borrarse.
     */
    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        if ($proveedor->ordenes()->exists() || $proveedor->facturas()->exists()) {
            return back()->with('error',
                'Ese proveedor ya tiene ordenes o facturas. Marcalo como inactivo en vez de eliminarlo.');
        }

        $proveedor->delete();

        return redirect()->route('compras.proveedores.index')
            ->with('success', 'Proveedor dado de baja correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Proveedor $proveedor): array
    {
        return [
            'proveedor' => $proveedor,
            'condicionesPago' => Catalogo::grupo('condiciones_pago')->get(),
            'monedas' => Catalogo::grupo('moneda')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }
}
