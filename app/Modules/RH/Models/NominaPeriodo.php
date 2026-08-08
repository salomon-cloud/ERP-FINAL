<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoNominaPeriodo;
use App\Modules\RH\Enums\FrecuenciaPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El calendario de la nomina: la quincena (o semana, o mes) que se va a pagar.
 *
 * Es el primero de los tres niveles que introdujo el ERP:
 * NominaPeriodo -> NominaCorrida -> Nomina (el recibo de v1).
 */
class NominaPeriodo extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'nomina_periodos';

    protected $fillable = [
        'organizacion_id',
        'codigo_periodo',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'frecuencia',
        'estado',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'frecuencia' => 'quincenal',
        'estado' => 'abierto',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_pago' => 'date',
        'frecuencia' => FrecuenciaPago::class,
        'estado' => EstadoNominaPeriodo::class,
    ];

    public function corridas(): HasMany
    {
        return $this->hasMany(NominaCorrida::class, 'periodo_id');
    }

    public function scopeAbiertos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoNominaPeriodo::Abierto);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where('codigo_periodo', 'like', '%'.$termino.'%');
    }
}
