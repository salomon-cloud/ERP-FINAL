<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Inventario\Enums\EstadoConteo;
use App\Modules\Inventario\Observers\ObservadorConteoInventario;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un inventario fisico: se congela lo que el sistema cree tener, se cuenta lo
 * que hay, y la diferencia se convierte en movimientos.
 */
#[ObservedBy([ObservadorConteoInventario::class])]
class ConteoInventario extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'conteos_inventario';

    protected $fillable = [
        'organizacion_id',
        'almacen_id',
        'ubicacion_id',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'estado' => EstadoConteo::class,
        'contado_en' => 'datetime',
        'cerrado_en' => 'datetime',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(ConteoLinea::class, 'conteo_id');
    }

    public function contadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contado_por');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        return blank($termino)
            ? $consulta
            : $consulta->where('numero_conteo', 'like', '%'.$termino.'%');
    }
}
