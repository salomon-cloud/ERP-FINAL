<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El autocompletado de producto que usan los carritos de Ventas y Compras.
 *
 * Devuelve lo que la linea necesita para armarse sola: precio, costo, tasa de
 * impuesto y disponible en el almacen elegido. Sin esto, capturar un pedido
 * obligaria a consultar el catalogo en otra pestana.
 *
 * Vive en el API del modulo dueno del catalogo, no duplicado en cada modulo que
 * lo consume.
 */
class ProductoController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function __invoke(Request $peticion): JsonResponse
    {
        $productos = Producto::query()
            ->with(['unidad'])
            ->activos()
            ->buscar($peticion->string('q')->toString())
            ->when($peticion->boolean('solo_vendibles'), fn ($consulta) => $consulta->where('es_vendible', true))
            ->when($peticion->boolean('solo_comprables'), fn ($consulta) => $consulta->where('es_comprable', true))
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        $almacenId = $peticion->integer('almacen_id') ?: null;

        return response()->json([
            'datos' => $productos->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'sku' => $producto->sku,
                'nombre' => $producto->nombre,
                'etiqueta' => $producto->etiqueta,
                'unidad' => $producto->unidad?->codigo,
                'costo' => (float) $producto->costo,
                'precio_venta' => (float) $producto->precio_venta,
                'impuesto_id' => $producto->impuesto_id,
                'disponible' => $almacenId !== null
                    ? $this->existencias->disponible($producto->id, $almacenId)
                    : null,
            ])->all(),
        ]);
    }
}
