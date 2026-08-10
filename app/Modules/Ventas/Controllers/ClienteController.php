<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Requests\GuardarClienteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** El catalogo de clientes y su ficha de 360 grados. */
class ClienteController extends Controller
{
    public function index(Request $peticion): View
    {
        $clientes = Cliente::query()
            ->with(['condicionPago', 'listaPrecio'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('condicion_pago_id'),
                fn ($consulta) => $consulta->where('condicion_pago_id', $peticion->integer('condicion_pago_id')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::catalogos.clientes.index', [
            'clientes' => $clientes,
            'condicionesPago' => Catalogo::grupo('condiciones_pago')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function create(): View
    {
        return view('ventas::catalogos.clientes.create', $this->datosDelFormulario(new Cliente));
    }

    public function store(GuardarClienteRequest $peticion): RedirectResponse
    {
        $cliente = Cliente::create($peticion->validated());

        return redirect()->route('ventas.clientes.show', $cliente)
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load(['condicionPago', 'listaPrecio']);

        return view('ventas::catalogos.clientes.show', [
            'cliente' => $cliente,
            // La ficha 360 sale de la vista v_historial_cliente, la misma que
            // lee CRM: asi los dos modulos pintan las mismas cifras.
            'historial' => $cliente->historial(),
            'cotizaciones' => $cliente->cotizaciones()->latest('id')->limit(5)->get(),
            'pedidos' => $cliente->pedidos()->latest('id')->limit(5)->get(),
            'facturas' => $cliente->facturas()->latest('id')->limit(10)->get(),
            'cobros' => $cliente->cobros()->latest('id')->limit(5)->get(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('ventas::catalogos.clientes.edit', $this->datosDelFormulario($cliente));
    }

    public function update(GuardarClienteRequest $peticion, Cliente $cliente): RedirectResponse
    {
        $cliente->update($peticion->validated());

        return redirect()->route('ventas.clientes.show', $cliente)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Baja logica, y solo si no tiene historia.
     *
     * Las llaves foraneas de cotizaciones, pedidos y facturas son RESTRICT: un
     * cliente con documentos no se borra ni debe borrarse.
     */
    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($cliente->facturas()->exists() || $cliente->pedidos()->exists()) {
            return back()->with('error',
                'Ese cliente ya tiene pedidos o facturas. Marcalo como inactivo en vez de eliminarlo.');
        }

        $cliente->delete();

        return redirect()->route('ventas.clientes.index')
            ->with('success', 'Cliente dado de baja correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Cliente $cliente): array
    {
        return [
            'cliente' => $cliente,
            'condicionesPago' => Catalogo::grupo('condiciones_pago')->get(),
            'monedas' => Catalogo::grupo('moneda')->get(),
            'listas' => ListaPrecio::orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ];
    }
}
