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
use Illuminate\Support\Carbon;

/**
 * Un lote de fabricacion con su caducidad.
 *
 * Importa en cuanto el catalogo lleva medicamento o alimento: el reporte de
 * proximos a vencer y la validacion de la recepcion salen de aqui.
 */
class Lote extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'numero_lote',
        'fecha_caducidad',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'fecha_caducidad' => 'date',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    /** Los que caducan dentro de los proximos N dias (o ya caducaron). */
    public function scopePorVencer(Builder $consulta, int $dias = 30): Builder
    {
        return $consulta->whereNotNull('fecha_caducidad')
            ->whereDate('fecha_caducidad', '<=', Carbon::today()->addDays($dias));
    }

    public function getEstaCaducadoAttribute(): bool
    {
        return $this->fecha_caducidad !== null && $this->fecha_caducidad->isPast();
    }
}
