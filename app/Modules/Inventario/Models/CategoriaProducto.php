<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El arbol de categorias del catalogo: Medicamentos, Insumos, Papeleria...
 *
 * ES LA PIEZA QUE EVITA CINCO CATALOGOS DE PRODUCTO. Un "medicamento" no es una
 * tabla distinta ni una pantalla distinta: es un producto con esta categoria.
 * La pantalla de productos filtra por aqui (docs/david.md D4).
 */
class CategoriaProducto extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'categorias_producto';

    protected $fillable = [
        'padre_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    /** El nombre con su rama: "Insumos / Curacion". */
    public function getNombreRutaAttribute(): string
    {
        return $this->padre !== null
            ? $this->padre->nombre.' / '.$this->nombre
            : $this->nombre;
    }

    /**
     * Esta categoria y todas sus descendientes.
     *
     * Filtrar productos por "Insumos" tiene que traer tambien los de sus
     * subcategorias; si no, el filtro miente. El arbol de un catalogo comercial
     * tiene pocos niveles, asi que se recorre en memoria y no con una CTE.
     *
     * @return array<int, int>
     */
    public function idsDeLaRama(): array
    {
        $ids = [(int) $this->getKey()];

        foreach ($this->hijas as $hija) {
            $ids = [...$ids, ...$hija->idsDeLaRama()];
        }

        return $ids;
    }
}
