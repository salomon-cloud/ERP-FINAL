<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoRequisicion;
use App\Modules\Compras\Observers\ObservadorRequisicion;
use App\Modules\RH\Models\Departamento;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una peticion interna de compra: "el area de curaciones necesita esto".
 *
 * No tiene proveedor ni precios, porque todavia no es una compra: es lo que
 * dispara una. `departamento_id` apunta a la tabla de RH, que es la razon de
 * que Compras migre despues de RH.
 */
#[ObservedBy([ObservadorRequisicion::class])]
class Requisicion extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'requisiciones';

    protected $fillable = [
        'organizacion_id',
        'departamento_id',
        'solicitante_id',
        'fecha_requerida',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
        'estado' => EstadoRequisicion::class,
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(RequisicionLinea::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_requisicion', 'like', '%'.$termino.'%')
            ->orWhere('notas', 'like', '%'.$termino.'%'));
    }
}
