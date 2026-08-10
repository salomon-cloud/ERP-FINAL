<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un almacen fisico. Todo movimiento de inventario pertenece a uno.
 *
 * El codigo es unico entre los activos (uq_almacenes_codigo sobre la columna
 * generada), asi que dar de baja un almacen libera su codigo para otro.
 */
class Almacen extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'almacenes';

    protected $fillable = [
        'organizacion_id',
        'codigo',
        'nombre',
        'direccion',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
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
        return $consulta->where('activo', true);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('codigo', 'like', '%'.$termino.'%')
            ->orWhere('nombre', 'like', '%'.$termino.'%'));
    }
}
