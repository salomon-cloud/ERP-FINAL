<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoRecepcion;
use App\Modules\Compras\Observers\ObservadorRecepcion;
use App\Modules\Inventario\Models\Almacen;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La entrada fisica de mercancia contra una orden de compra.
 *
 * ES EL DOCUMENTO CRITICO DE COMPRAS: aplicarlo es lo que hace que el
 * inventario exista. Una orden es una promesa; una recepcion es un hecho.
 *
 * Una orden puede tener varias recepciones (llegaron 60 el lunes y 40 el
 * jueves): la recepcion parcial es natural por lineas, sin nada especial.
 */
#[ObservedBy([ObservadorRecepcion::class])]
class Recepcion extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'recepciones';

    protected $fillable = [
        'organizacion_id',
        'orden_compra_id',
        'almacen_id',
        'fecha',
        'recibido_por',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'fecha' => 'date',
        'estado' => EstadoRecepcion::class,
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(RecepcionLinea::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_recepcion', 'like', '%'.$termino.'%')
            ->orWhereHas('ordenCompra', fn (Builder $orden) => $orden
                ->where('numero_orden', 'like', '%'.$termino.'%')));
    }
}
