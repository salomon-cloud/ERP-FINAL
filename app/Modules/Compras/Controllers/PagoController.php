<?php

declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Compartido\Traits\ControlaDocumentos;
use App\Modules\Compras\Enums\EstadoPago;
use App\Modules\Compras\Enums\FormaPagoProveedor;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\Pago;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\GuardarPagoRequest;
use App\Modules\Compras\Services\ServicioPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Pagos a proveedores. */
class PagoController extends Controller
{
    use ControlaDocumentos;

    public function __construct(private readonly ServicioPago $servicio) {}

    public function index(Request $peticion): View
    {
        $pagos = Pago::query()
            ->with(['proveedor', 'factura'])
            ->buscar($peticion->string('buscar')->toString())
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->filled('proveedor_id'),
                fn ($consulta) => $consulta->where('proveedor_id', $peticion->integer('proveedor_id')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('fecha', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('fecha', '<=', $peticion->date('hasta')))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras::paginas.pagos.index', [
            'pagos' => $pagos,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            'estados' => OpcionesEnum::de(EstadoPago::class),
        ]);
    }

    public function create(Request $peticion): View
    {
        $factura = $peticion->filled('factura_proveedor_id')
            ? FacturaProveedor::find($peticion->integer('factura_proveedor_id'))
            : null;

        $pago = new Pago([
            'proveedor_id' => $factura?->proveedor_id ?? ($peticion->integer('proveedor_id') ?: null),
            'factura_proveedor_id' => $factura?->id,
            'fecha' => now()->toDateString(),
            // Se propone el saldo pendiente: lo normal es pagar la factura
            // completa, y teclear el importe de nuevo solo invita a errores.
            'monto' => $factura?->saldo,
        ]);

        return view('compras::paginas.pagos.create', $this->datosDelFormulario($pago));
    }

    public function store(GuardarPagoRequest $peticion): RedirectResponse
    {
        $pago = Pago::create($peticion->validated());

        return redirect()->route('compras.pagos.show', $pago)
            ->with('success', "Pago {$pago->numero_pago} registrado. Aplicalo cuando salga del banco.");
    }

    public function show(Pago $pago): View
    {
        $pago->load(['proveedor', 'factura']);

        return view('compras::paginas.pagos.show', [
            'pago' => $pago,
            'bitacora' => $this->bitacoraDe($pago),
        ]);
    }

    public function edit(Pago $pago): View
    {
        abort_unless($pago->estado->esEditable(), 403);

        return view('compras::paginas.pagos.edit', $this->datosDelFormulario($pago));
    }

    public function update(GuardarPagoRequest $peticion, Pago $pago): RedirectResponse
    {
        abort_unless($pago->estado->esEditable(), 403);

        $pago->update($peticion->validated());

        return redirect()->route('compras.pagos.show', $pago)
            ->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Pago $pago): RedirectResponse
    {
        abort_unless($pago->estado->esEditable(), 403);

        $pago->delete();

        return redirect()->route('compras.pagos.index')
            ->with('success', 'Pago eliminado correctamente.');
    }

    public function aplicar(Pago $pago): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->aplicar($pago),
            $pago,
            'compras.pagos.show',
            'Pago aplicado.',
        );
    }

    public function cancelar(Pago $pago): RedirectResponse
    {
        return $this->ejecutarAccion(
            fn () => $this->servicio->cancelar($pago),
            $pago,
            'compras.pagos.show',
            'Pago cancelado. El saldo volvio a la factura.',
        );
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(Pago $pago): array
    {
        return [
            'pago' => $pago,
            'proveedores' => Proveedor::activos()->orderBy('nombre')->get(),
            // Solo lo que de verdad se puede pagar: contabilizadas con saldo.
            'facturas' => FacturaProveedor::query()
                ->with('proveedor')
                ->whereIn('estado', ['contabilizada', 'pagada_parcial'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'formasPago' => OpcionesEnum::de(FormaPagoProveedor::class),
            'cuentas' => DB::table('cuentas_bancarias')
                ->whereNull('deleted_at')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'banco']),
        ];
    }
}
