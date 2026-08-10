<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EL catalogo de productos del ERP. Inventario es su unico dueno.
 *
 * Ventas y Compras leen ESTE modelo; jamas declaran uno propio sobre la tabla
 * ni guardan una segunda copia del nombre o del precio -- salvo la foto que
 * cada linea de documento se queda al crearse, que es otra cosa: eso es
 * historia, no duplicado.
 *
 * `costo` y `precio_venta` son los valores VIGENTES del catalogo. Cambiarlos no
 * reescribe ningun documento ya capturado.
 */
class Producto extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'productos';

    protected $fillable = [
        'organizacion_id',
        'sku',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_id',
        'impuesto_id',
        'costo',
        'precio_venta',
        'stock_minimo',
        'stock_maximo',
        'es_vendible',
        'es_comprable',
        'es_inventariable',
        'rastrea_serie',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'activo',
        'costo' => 0,
        'precio_venta' => 0,
        'es_vendible' => true,
        'es_comprable' => true,
        'es_inventariable' => true,
        'rastrea_serie' => false,
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock_minimo' => 'decimal:6',
        'stock_maximo' => 'decimal:6',
        'es_vendible' => 'boolean',
        'es_comprable' => 'boolean',
        'es_inventariable' => 'boolean',
        'rastrea_serie' => 'boolean',
        'estado' => EstadoActivacion::class,
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_id');
    }

    public function codigosBarras(): HasMany
    {
        return $this->hasMany(CodigoBarras::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function numerosSerie(): HasMany
    {
        return $this->hasMany(NumeroSerie::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function reglasReorden(): HasMany
    {
        return $this->hasMany(ReglaReorden::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoActivacion::Activo);
    }

    public function scopeVendibles(Builder $consulta): Builder
    {
        return $consulta->activos()->where('es_vendible', true);
    }

    public function scopeComprables(Builder $consulta): Builder
    {
        return $consulta->activos()->where('es_comprable', true);
    }

    /**
     * La busqueda de la barra de filtros y del autocompletado del carrito:
     * SKU, nombre, descripcion o codigo de barras.
     */
    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('sku', 'like', '%'.$termino.'%')
                ->orWhere('nombre', 'like', '%'.$termino.'%')
                ->orWhere('descripcion', 'like', '%'.$termino.'%')
                ->orWhereHas('codigosBarras',
                    fn (Builder $codigo) => $codigo->where('codigo', 'like', '%'.$termino.'%'));
        });
    }

    /**
     * Los productos de una categoria y de todas sus subcategorias.
     *
     * Ver CategoriaProducto::idsDeLaRama(): filtrar por la rama y no por el id
     * pelado es lo que hace que las pestanas del catalogo unificado digan la
     * verdad.
     */
    public function scopeDeLaCategoria(Builder $consulta, ?int $categoriaId): Builder
    {
        if ($categoriaId === null) {
            return $consulta;
        }

        $categoria = CategoriaProducto::with('hijas.hijas')->find($categoriaId);

        return $consulta->whereIn('categoria_id', $categoria?->idsDeLaRama() ?? [$categoriaId]);
    }

    /** El codigo de barras principal, si tiene. */
    public function getCodigoPrincipalAttribute(): ?string
    {
        return $this->codigosBarras->firstWhere('es_principal', true)?->codigo
            ?? $this->codigosBarras->first()?->codigo;
    }

    /** "SKU-001 - Guantes de nitrilo", para los selectores. */
    public function getEtiquetaAttribute(): string
    {
        return $this->sku.' - '.$this->nombre;
    }
}
