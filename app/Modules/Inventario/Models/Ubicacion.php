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
 * Un pasillo, rack o anaquel dentro de un almacen.
 *
 * `es_surtible` distingue la ubicacion de la que se puede tomar mercancia para
 * surtir de la que no (cuarentena, mercancia danada, area de revision).
 */
class Ubicacion extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'almacen_id',
        'codigo',
        'nombre',
        'es_surtible',
        'activo',
    ];

    protected $attributes = [
        'es_surtible' => true,
        'activo' => true,
    ];

    protected $casts = [
        'es_surtible' => 'boolean',
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    /** Las que sirven para surtir un pedido o recibir una compra. */
    public function scopeSurtibles(Builder $consulta): Builder
    {
        return $consulta->where('activo', true)->where('es_surtible', true);
    }
}
