<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoNominaCorrida;
use App\Modules\RH\Observers\ObservadorNominaCorrida;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El procesamiento de un periodo: agrupa los recibos de todos los empleados y
 * lleva los totales de la corrida.
 *
 * Es un documento del ERP en el sentido del Apendice A.2: tiene folio
 * (`numero_corrida`, que asigna un Observer desde ServicioFolios), ciclo de
 * vida (borrador -> procesada -> aplicada) y bloqueo optimista (version_fila).
 * Aplicarla genera la poliza en Finanzas.
 *
 * `poliza_id` se queda como columna sin relacion Eloquent a proposito: la tabla
 * `polizas` es de Finanzas y ese modulo todavia no publica su modelo. Cuando lo
 * haga, aqui se agrega el belongsTo -- nunca un modelo propio sobre su tabla.
 */
#[ObservedBy([ObservadorNominaCorrida::class])]
class NominaCorrida extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'nomina_corridas';

    /**
     * Ni el folio ni los totales ni el estado se asignan desde una peticion:
     * los pone el Observer o ServicioCorridaNomina. Por eso no son fillable.
     */
    protected $fillable = [
        'organizacion_id',
        'periodo_id',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'borrador',
        'total_empleados' => 0,
        'total_percepciones' => 0,
        'total_deducciones' => 0,
        'total_neto' => 0,
        'version_fila' => 1,
    ];

    protected $casts = [
        'total_empleados' => 'integer',
        'total_percepciones' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'total_neto' => 'decimal:2',
        'generada_en' => 'datetime',
        'aplicada_en' => 'datetime',
        'version_fila' => 'integer',
        'estado' => EstadoNominaCorrida::class,
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(NominaPeriodo::class, 'periodo_id');
    }

    /** Los recibos que genero esta corrida. */
    public function recibos(): HasMany
    {
        return $this->hasMany(Nomina::class, 'corrida_id');
    }

    public function procesadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procesada_por');
    }

    public function aprobadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where('numero_corrida', 'like', '%'.$termino.'%');
    }
}
