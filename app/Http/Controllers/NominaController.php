<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Nomina;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NominaController extends Controller
{
    public function index(Request $request)
    {
        $query = Nomina::with('empleado')
            ->when(auth()->user()->role === 'Empleado', fn ($q) => $q->where('empleado_id', auth()->user()->empleado_id))
            ->when($request->empleado_id, fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->desde, fn ($q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->hasta, fn ($q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha));

        $resumen = [
            'pendiente' => (clone $query)->where('estado', 'pendiente')->sum('total_pagar'),
            'pagada' => (clone $query)->where('estado', 'pagada')->sum('total_pagar'),
        ];

        $nominas = $query->latest()->paginate(10)->withQueryString();

        return view('nominas.index', ['nominas' => $nominas, 'empleados' => Empleado::orderBy('nombre')->get(), 'resumen' => $resumen]);
    }

    public function create()
    {
        $this->denyEmployeeRole();

        return view('nominas.create', ['nomina' => new Nomina, 'empleados' => Empleado::where('estado', 'activo')->get()]);
    }

    public function store(Request $request)
    {
        $this->denyEmployeeRole();
        $data = $this->validated($request);
        $this->autoFillDeducciones($data);
        $data['total_pagar'] = Nomina::calcularTotal($data);
        $this->assertPositiveTotal($data['total_pagar']);
        $data['created_by'] = auth()->id();
        Nomina::create($data);

        return redirect()->route('nominas.index')->with('success', 'Nomina generada correctamente.');
    }

    public function show(Nomina $nomina)
    {
        $this->authorizeEmployee($nomina);
        $nomina->load('empleado.departamento', 'empleado.puesto', 'creador', 'pagador');

        return view('nominas.show', compact('nomina'));
    }

    public function edit(Nomina $nomina)
    {
        $this->denyEmployeeRole();
        $this->denyIfPaid($nomina);

        return view('nominas.edit', ['nomina' => $nomina, 'empleados' => Empleado::where('estado', 'activo')->get()]);
    }

    public function update(Request $request, Nomina $nomina)
    {
        $this->denyEmployeeRole();
        $this->denyIfPaid($nomina);
        $data = $this->validated($request, $nomina);
        $this->autoFillDeducciones($data);
        $data['total_pagar'] = Nomina::calcularTotal($data);
        $this->assertPositiveTotal($data['total_pagar']);
        $nomina->update($data);

        return redirect()->route('nominas.index')->with('success', 'Nomina actualizada correctamente.');
    }

    public function destroy(Nomina $nomina)
    {
        $this->denyEmployeeRole();
        $this->denyIfPaid($nomina);
        $nomina->delete();

        return redirect()->route('nominas.index')->with('success', 'Nomina eliminada correctamente.');
    }

    public function markPaid(Nomina $nomina)
    {
        abort_unless($nomina->estado === 'pendiente', 422, 'Solo se pueden pagar nominas pendientes.');
        $nomina->update([
            'estado' => 'pagada',
            'paid_by' => auth()->id(),
            'fecha_pago_real' => now(),
        ]);

        return back()->with('success', 'Nomina marcada como pagada.');
    }

    public function cancel(Nomina $nomina)
    {
        abort_unless($nomina->estado === 'pendiente', 422, 'Solo se pueden cancelar nominas pendientes.');
        $nomina->update(['estado' => 'cancelada']);

        return back()->with('success', 'Nomina cancelada.');
    }

    private function validated(Request $request, ?Nomina $nomina = null): array
    {
        $unique = Rule::unique('nominas', 'periodo_pago')->where('empleado_id', $request->input('empleado_id'));
        if ($nomina) {
            $unique->ignore($nomina->id);
        }

        return $request->validate([
            'empleado_id' => ['required', 'exists:empleados,id'], 'periodo_pago' => ['required', 'max:120', $unique], 'fecha_pago' => ['required', 'date'],
            'sueldo_base' => ['required', 'numeric', 'min:0'], 'bonos' => ['nullable', 'numeric', 'min:0'], 'horas_extra' => ['nullable', 'numeric', 'min:0'],
            'deducciones' => ['nullable', 'numeric', 'min:0'], 'isr' => ['nullable', 'numeric', 'min:0'], 'imss' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['required', 'in:pendiente,pagada,cancelada'],
            'metodo_pago' => ['nullable', 'in:transferencia,efectivo,cheque'],
        ]);
    }

    private function autoFillDeducciones(array &$data): void
    {
        $sueldo = (float) $data['sueldo_base'];
        if ($sueldo > 0 && ! (float) ($data['isr'] ?? 0) && ! (float) ($data['imss'] ?? 0)) {
            $data['isr'] = Nomina::sugerirIsr($sueldo);
            $data['imss'] = Nomina::sugerirImss($sueldo);
        }
    }

    private function assertPositiveTotal(float $total): void
    {
        if ($total < 0) {
            throw ValidationException::withMessages([
                'total_pagar' => 'El total a pagar no puede ser negativo. Revisa deducciones, ISR e IMSS.',
            ]);
        }
    }

    private function denyIfPaid(Nomina $nomina): void
    {
        abort_unless($nomina->estado !== 'pagada', 422, 'Una nomina pagada no puede ser modificada ni eliminada.');
    }

    private function authorizeEmployee(Nomina $nomina): void
    {
        if (auth()->user()->role === 'Empleado' && auth()->user()->empleado_id !== $nomina->empleado_id) {
            abort(403);
        }
    }

    private function denyEmployeeRole(): void
    {
        if (auth()->user()->role === 'Empleado') {
            abort(403);
        }
    }
}
