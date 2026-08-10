<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Ventas\Enums\EstadoCobro;
use App\Modules\Ventas\Enums\FormaPagoCobro;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\Cobro;
use App\Modules\Ventas\Models\Factura;
use App\Modules\Ventas\Requests\GuardarCobroRequest;
use App\Modules\Ventas\Services\ServicioCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Cobros a clientes, totales o parciales. */
class CobroController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioCobro $servicio) {}

    public function index(Request $peticion): View
    {
        $cobros = Cobro::query()
            ->with(['cliente', 'factura'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('cliente_id'),
                fn ($consulta) => $consulta->where('cliente_id', $peticion->integer('cliente_id')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('ventas::paginas.cobros.index', [
            'cobros' => $cobros,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoCobro::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $factura = $peticion->filled('factura_id')
            ? Factura::find($peticion->integer('factura_id'))
            : null;

        $cobro = new Cobro([
            'cliente_id' => $factura?->cliente_id ?? ($peticion->integer('cliente_id') ?: null),
            'factura_id' => $factura?->id,
            'fecha' => now()->toDateString(),
            // Se propone el saldo: lo normal es cobrar la factura completa.
            'monto' => $factura?->saldo,
        ]);

        return view('ventas::paginas.cobros.create', $this->datosDelFormulario($cobro));
    }

    public function store(GuardarCobroRequest $peticion): RedirectResponse
    {
        $cobro = Cobro::create($peticion->validated());

        return redirect()->route('ventas.cobros.show', $cobro)
            ->with('success', "Cobro {$cobro->numero_cobro} registrado. Aplicalo cuando entre el dinero.");
    }

    public function show(Cobro $cobro): View
    {
        $cobro->load(['cliente', 'factura']);

        return view('ventas::paginas.cobros.show', [
            'cobro' => $cobro,
            // Para asignarle factura a un cobro a cuenta.
            'facturasDelCliente' => Factura::query()
                ->where('cliente_id', $cobro->cliente_id)
                ->whereIn('estado', ['emitida', 'cobrada_parcial', 'vencida'])
                ->orderByDesc('id')
                ->get(),
            'bitacora' => $this->bitacoraDe($cobro),
        ]);
    }

    public function edit(Cobro $cobro): View
    {
        abort_unless($cobro->estado->esEditable(), 403);

        return view('ventas::paginas.cobros.edit', $this->datosDelFormulario($cobro));
    }

    public function update(GuardarCobroRequest $peticion, Cobro $cobro): RedirectResponse
    {
        abort_unless($cobro->estado->esEditable(), 403);

        $cobro->update($peticion->validated());

        return redirect()->route('ventas.cobros.show', $cobro)
            ->with('success', 'Cobro actualizado correctamente.');
    }

    public function destroy(Cobro $cobro): RedirectResponse
    {
        abort_unless($cobro->estado->esEditable(), 403);

        $cobro->delete();

        return redirect()->route('ventas.cobros.index')
            ->with('success', 'Cobro eliminado correctamente.');
    }

    public function aplicar(Cobro $cobro): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->aplicar($cobro),
            $cobro,
            'ventas.cobros.show',
            'Cobro aplicado.',
        );
    }

    /** Asigna un cobro a cuenta a una factura concreta. */
    public function asignarFactura(Request $peticion, Cobro $cobro): RedirectResponse
    {
        $peticion->validate(['factura_id' => ['required', 'integer', 'exists:facturas,id']]);

        return $this->ejecutarAccion(
            fn () => $this->servicio->asignarFactura($cobro, Factura::findOrFail($peticion->integer('factura_id'))),
            $cobro,
            'ventas.cobros.show',
            'Cobro asignado a la factura.',
        );
    }

    public function cancelar(Cobro $cobro): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($cobro),
            $cobro,
            'ventas.cobros.show',
            'Cobro cancelado. El saldo volvio a la factura.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Cobro $cobro): array
    {
        return [
            'cobro' => $cobro,
            'clientes' => Cliente::activos()->orderBy('nombre')->get(),
            'facturas' => Factura::query()
                ->with('cliente')
                ->whereIn('estado', ['emitida', 'cobrada_parcial', 'vencida'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'formasPago' => OpcionesEnum::de(FormaPagoCobro::class),
            'cuentas' => DB::table('cuentas_bancarias')
                ->whereNull('deleted_at')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'banco']),
        ];
    }
}
