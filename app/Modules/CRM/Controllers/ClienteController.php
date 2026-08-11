<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Ventas\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La vista comercial de los clientes dentro del CRM.
 *
 * El catalogo de clientes es de Ventas y aqui solo se lee: no hay segunda fuente
 * de verdad. El listado cruza la vista v_historial_cliente (la misma que pinta
 * la ficha 360 en Ventas) para mostrar facturado, saldo y oportunidades sin
 * disparar una consulta por fila.
 */
class ClienteController extends Controller
{
    public function index(Request $peticion): View
    {
        $clientes = Cliente::query()
            ->with(['condicionPago', 'listaPrecio'])
            ->leftJoin('v_historial_cliente as vh', 'vh.cliente_id', '=', 'clientes.id')
            ->select(
                'clientes.*',
                'vh.monto_facturado as facturado_total',
                'vh.saldo_pendiente as saldo_total',
                'vh.oportunidades_abiertas as oportunidades_abiertas',
            )
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('clientes.estado', $peticion->input('estado')))
            ->when($peticion->filled('condicion_pago_id'),
                fn ($consulta) => $consulta->where('clientes.condicion_pago_id', $peticion->integer('condicion_pago_id')))
            ->orderBy('clientes.nombre')
            ->paginate(10)
            ->withQueryString();

        return view('crm::catalogos.clientes.index', [
            'clientes' => $clientes,
            'condicionesPago' => Catalogo::grupo('condiciones_pago')->get(),
            'estados' => OpcionesEnum::de(EstadoActivacion::class),
        ]);
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load(['condicionPago', 'listaPrecio']);

        return view('crm::catalogos.clientes.show', [
            'cliente' => $cliente,
            // La ficha 360 sale de v_historial_cliente, la misma vista que usa
            // Ventas: los dos modulos pintan exactamente las mismas cifras.
            'historial' => $cliente->historial(),
        ]);
    }
}
