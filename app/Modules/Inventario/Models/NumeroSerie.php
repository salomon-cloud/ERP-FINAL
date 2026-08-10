<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Inventario\Enums\EstadoNumeroSerie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una pieza identificada una por una, para los productos con rastrea_serie.
 */
class NumeroSerie extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'numeros_serie';

    protected $fillable = [
        'producto_id',
        'numero_serie',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'en_stock',
    ];

    protected $casts = [
        'estado' => EstadoNumeroSerie::class,
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function scopeEnStock(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoNumeroSerie::EnStock);
    }
}
