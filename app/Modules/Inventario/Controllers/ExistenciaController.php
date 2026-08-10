<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\CategoriaProducto;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Que tengo y donde": la pantalla que mas se consulta del modulo.
 *
 * Muestra las tres cifras juntas -- existencia, apartado y disponible -- porque
 * el almacenista y el vendedor necesitan cosas distintas: uno cuenta cajas, el
 * otro promete entregas.
 */
class ExistenciaController extends Controller
{
    public function __construct(private readonly ServicioExistencias $existencias) {}

    public function index(Request $peticion): View
    {
        $porUbicacion = $peticion->boolean('por_ubicacion');

        $existencias = $this->existencias
            ->consulta($this->filtros($peticion), $porUbicacion)
            ->paginate(15)
            ->withQueryString();

        return view('inventario::paginas.existencias.index', [
            'existencias' => $existencias,
            'almacenes' => Almacen::activos()->orderBy('codigo')->get(),
            'categorias' => CategoriaProducto::activos()->orderBy('nombre')->get(),
            'porUbicacion' => $porUbicacion,
        ]);
    }

    /**
     * Traduce la barra de filtros a lo que entiende el servicio.
     *
     * La categoria se expande a toda su rama, para que filtrar por "Insumos"
     * traiga tambien lo de sus subcategorias.
     *
     * @return array<string, mixed>
     */
    private function filtros(Request $peticion): array
    {
        $filtros = [
            'buscar' => $peticion->string('buscar')->toString(),
            'almacen_id' => $peticion->integer('almacen_id') ?: null,
            'solo_con_existencia' => ! $peticion->boolean('incluir_en_cero'),
        ];

        if ($peticion->filled('categoria_id')) {
            $categoria = CategoriaProducto::with('hijas.hijas')->find($peticion->integer('categoria_id'));
            $filtros['categoria_ids'] = $categoria?->idsDeLaRama() ?? [];
        }

        return $filtros;
    }
}
