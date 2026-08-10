<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\CodigoBarras;
use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Requests\GuardarCodigoBarrasRequest;
use App\Modules\Inventario\Requests\GuardarLoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Lotes y codigos de barras de un producto.
 *
 * No tienen pantalla propia: se administran desde la ficha del producto, que es
 * el unico lugar donde tienen sentido. Por eso este controlador solo expone las
 * mutaciones y siempre regresa a la ficha.
 */
class LoteController extends Controller
{
    public function guardarLote(GuardarLoteRequest $peticion, Producto $producto): RedirectResponse
    {
        $producto->lotes()->create($peticion->validated());

        return back()->with('success', 'Lote registrado correctamente.');
    }

    public function eliminarLote(Producto $producto, Lote $lote): RedirectResponse
    {
        abort_unless((int) $lote->producto_id === (int) $producto->id, 404);

        if ($lote->movimientos()->exists()) {
            return back()->with('error',
                'Ese lote ya tiene movimientos. Marcalo como inactivo en vez de eliminarlo.');
        }

        $lote->delete();

        return back()->with('success', 'Lote eliminado correctamente.');
    }

    public function guardarCodigo(GuardarCodigoBarrasRequest $peticion, Producto $producto): RedirectResponse
    {
        DB::transaction(function () use ($peticion, $producto): void {
            $datos = $peticion->validated();

            // Solo puede haber un principal: marcar uno nuevo desmarca al viejo.
            if ($datos['es_principal']) {
                $producto->codigosBarras()->update(['es_principal' => false]);
            }

            $producto->codigosBarras()->create($datos);
        });

        return back()->with('success', 'Codigo de barras agregado.');
    }

    public function eliminarCodigo(Producto $producto, CodigoBarras $codigo): RedirectResponse
    {
        abort_unless((int) $codigo->producto_id === (int) $producto->id, 404);

        $codigo->delete();

        return back()->with('success', 'Codigo de barras eliminado.');
    }
}
