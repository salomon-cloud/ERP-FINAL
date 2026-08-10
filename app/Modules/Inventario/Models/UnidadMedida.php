<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PZA, CAJA, KG, L...
 *
 * `factor_base` dice cuantas unidades base vale una: una CAJA de 12 tiene
 * factor 12. El inventario se guarda SIEMPRE en unidad base
 * (movimientos_inventario.cantidad), asi que vender una caja saca doce piezas.
 * La conversion la hace aCantidadBase() y nadie multiplica a mano.
 */
class UnidadMedida extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'codigo',
        'nombre',
        'factor_base',
        'es_base',
    ];

    protected $attributes = [
        'factor_base' => 1,
        'es_base' => false,
    ];

    protected $casts = [
        'factor_base' => 'decimal:6',
        'es_base' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'unidad_id');
    }

    /** Convierte una cantidad expresada en esta unidad a unidades base. */
    public function aCantidadBase(float $cantidad): float
    {
        return $cantidad * (float) $this->factor_base;
    }

    /** El camino de vuelta, para mostrar una existencia en la unidad del producto. */
    public function desdeCantidadBase(float $cantidadBase): float
    {
        $factor = (float) $this->factor_base;

        return $factor > 0 ? $cantidadBase / $factor : $cantidadBase;
    }
}
