<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Inventario\Enums\EstadoAjuste;
use App\Modules\Inventario\Observers\ObservadorAjusteInventario;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El documento que respalda un cambio de existencia sin operacion comercial:
 * merma, rotura, robo, error de captura.
 *
 * Nunca se edita el stock a mano; se levanta un ajuste con su motivo, alguien
 * lo aplica y de ahi salen los movimientos. El motivo es obligatorio a nivel de
 * esquema (columna NOT NULL) justo para que no se pueda ajustar "porque si".
 */
#[ObservedBy([ObservadorAjusteInventario::class])]
class AjusteInventario extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'organizacion_id',
        'motivo',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'estado' => EstadoAjuste::class,
        'aprobado_en' => 'datetime',
        'aplicado_en' => 'datetime',
    ];

    public function lineas(): HasMany
    {
        return $this->hasMany(AjusteLinea::class, 'ajuste_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_ajuste', 'like', '%'.$termino.'%')
            ->orWhere('motivo', 'like', '%'.$termino.'%'));
    }
}
