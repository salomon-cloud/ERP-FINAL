<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Support\OpcionesEnum;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * El kardex: el libro de movimientos, filtrable.
 *
 * Es solo lectura a proposito. No hay forma de crear, editar ni borrar un
 * movimiento desde la interfaz: los movimientos los escriben los servicios a
 * partir de documentos, y esa es justamente la garantia de que la existencia
 * cuadra con su historia.
 */
class MovimientoController extends Controller
{
    public function index(Request $peticion): View
    {
        $movimientos = MovimientoInventario::query()
            ->with(['producto', 'almacen', 'ubicacion', 'lote', 'aplicadoPor'])
            ->when($peticion->filled('producto_id'),
                fn ($consulta) => $consulta->where('producto_id', $peticion->integer('producto_id')))
            ->when($peticion->filled('almacen_id'),
                fn ($consulta) => $consulta->where('almacen_id', $peticion->integer('almacen_id')))
            ->when($peticion->filled('tipo_movimiento'),
                fn ($consulta) => $consulta->where('tipo_movimiento', $peticion->input('tipo_movimiento')))
            ->when($peticion->filled('desde'),
                fn ($consulta) => $consulta->whereDate('aplicado_en', '>=', $peticion->date('desde')))
            ->when($peticion->filled('hasta'),
                fn ($consulta) => $consulta->whereDate('aplicado_en', '<=', $peticion->date('hasta')))
            ->when($peticion->filled('buscar'), fn ($consulta) => $consulta
                ->whereHas('producto', fn ($producto) => $producto
                    ->buscar($peticion->string('buscar')->toString())))
            ->orderByDesc('aplicado_en')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventario::paginas.movimientos.index', [
            'movimientos' => $movimientos,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'tipos' => OpcionesEnum::de(TipoMovimiento::class),
        ]);
    }
}
