<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El minimo y el maximo de un producto EN UN ALMACEN concreto.
 *
 * `productos.stock_minimo` es el minimo global del catalogo; esta tabla lo
 * afina por almacen, que es lo que de verdad dispara la compra. El comando
 * `inventario:reorden` lee de aqui.
 */
class ReglaReorden extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'reglas_reorden';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'cantidad_minima',
        'cantidad_maxima',
        'cantidad_reorden',
        'dias_entrega',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
        'dias_entrega' => 0,
    ];

    protected $casts = [
        'cantidad_minima' => 'decimal:6',
        'cantidad_maxima' => 'decimal:6',
        'cantidad_reorden' => 'decimal:6',
        'dias_entrega' => 'integer',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    /**
     * Cuanto habria que pedir para volver al nivel objetivo.
     *
     * Si hay `cantidad_reorden` configurada se pide eso (el lote economico que
     * negocio ya definio); si no, lo que falte para llegar al maximo.
     */
    public function cantidadSugerida(float $disponible): float
    {
        $reorden = (float) $this->cantidad_reorden;

        if ($reorden > 0) {
            return $reorden;
        }

        $maximo = (float) $this->cantidad_maxima;
        $objetivo = $maximo > 0 ? $maximo : (float) $this->cantidad_minima;

        return max($objetivo - $disponible, 0.0);
    }
}
