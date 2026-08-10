<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una lista de precios: mayoreo, menudeo, convenio con un cliente grande.
 *
 * Solo una puede ser la predeterminada; la marca `es_predeterminada` y
 * ServicioResolverPrecio la usa cuando el cliente no tiene lista propia.
 */
class ListaPrecio extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'listas_precios';

    protected $fillable = [
        'organizacion_id',
        'codigo',
        'nombre',
        'moneda',
        'es_predeterminada',
    ];

    protected $attributes = [
        'moneda' => 'MXN',
        'es_predeterminada' => false,
    ];

    protected $casts = [
        'es_predeterminada' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ListaPrecioItem::class, 'lista_precio_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'lista_precio_id');
    }

    public function scopePredeterminada(Builder $consulta): Builder
    {
        return $consulta->where('es_predeterminada', true);
    }
}
