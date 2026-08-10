<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Inventario\Enums\EstadoAjuste;
use App\Modules\Inventario\Enums\EstadoConteo;
use App\Modules\Inventario\Enums\EstadoTraspaso;
use App\Modules\Inventario\Models\AjusteInventario;
use App\Modules\Inventario\Models\ConteoInventario;
use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\MovimientoInventario;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\Traspaso;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\View\View;

/**
 * El tablero del modulo. Sustituye la pagina provisional conservando el nombre
 * de ruta `inventario.dashboard`, que es al que apunta la barra lateral.
 *
 * Cada KPI es una consulta real y lleva a la lista que lo demuestra: un numero
 * que no se puede abrir no sirve para decidir nada.
 */
class TableroController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function __invoke(): View
    {
        $bajoMinimo = $this->existencias->bajoMinimo();

        return view('inventario::dashboard.index', [
            'productosActivos' => Producto::where('estado', EstadoActivacion::Activo)->count(),
            'bajoMinimo' => $bajoMinimo->count(),
            'productosBajoMinimo' => $bajoMinimo->take(8),
            'valorInventario' => $this->valorTotal(),
            'lotesPorVencer' => Lote::activos()->porVencer(30)->count(),
            'proximosAVencer' => Lote::activos()->porVencer(30)->with('producto')
                ->orderBy('fecha_caducidad')->limit(6)->get(),
            'traspasosEnTransito' => Traspaso::where('estado', EstadoTraspaso::EnTransito)->count(),
            'ajustesPendientes' => AjusteInventario::where('estado', EstadoAjuste::Borrador)->count(),
            'conteosAbiertos' => ConteoInventario::whereIn('estado', [
                EstadoConteo::Borrador, EstadoConteo::EnProceso, EstadoConteo::Contado,
            ])->count(),
            'ultimosMovimientos' => MovimientoInventario::with(['producto', 'almacen', 'aplicadoPor'])
                ->orderByDesc('aplicado_en')->limit(10)->get(),
        ]);
    }

    /** El valor del inventario fisico: existencia por costo, sin contar apartados. */
    private function valorTotal(): float
    {
        return (float) $this->existencias->consulta()->get()->sum('valor_inventario');
    }
}
