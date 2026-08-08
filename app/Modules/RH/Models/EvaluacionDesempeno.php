<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoEvaluacionDesempeno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La evaluacion de desempeno de un empleado en un periodo (2026-S1).
 *
 * Un empleado tiene una sola evaluacion por periodo: lo garantiza el indice
 * uq_evaluacion_periodo.
 *
 * `evaluador_id` apunta a empleados, no a users: quien evalua es una persona de
 * la organizacion (normalmente el jefe), tenga o no cuenta de acceso.
 */
class EvaluacionDesempeno extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'evaluaciones_desempeno';

    protected $fillable = [
        'empleado_id',
        'evaluador_id',
        'periodo_evaluado',
        'calificacion',
        'fortalezas',
        'areas_mejora',
        'objetivos',
        'estado',
        'evaluado_en',
    ];

    /** El mismo valor por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'calificacion' => 'decimal:2',
        // Lista de objetos { objetivo, metrica, meta, logrado }
        'objetivos' => 'array',
        'evaluado_en' => 'datetime',
        'estado' => EstadoEvaluacionDesempeno::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'evaluador_id');
    }

    public function scopeDelPeriodo(Builder $consulta, string $periodo): Builder
    {
        return $consulta->where('periodo_evaluado', $periodo);
    }
}
