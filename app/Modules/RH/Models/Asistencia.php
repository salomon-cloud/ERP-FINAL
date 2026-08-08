<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Observers\ObservadorAsistencia;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La tabla `asistencias` de SISEN v1, mas horas trabajadas, notas y quien
 * verifico el registro.
 *
 * Un empleado tiene una sola asistencia por dia: lo garantiza el indice unico
 * uq_asistencias_empleado_fecha, no una validacion de la aplicacion.
 *
 * `horas_trabajadas` no se captura a mano: lo deriva ObservadorAsistencia a
 * partir de la entrada y la salida.
 */
#[ObservedBy([ObservadorAsistencia::class])]
class Asistencia extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'asistencias';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'horas_trabajadas',
        'estado',
        'notas',
        'verificado_por',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'presente',
        'horas_trabajadas' => 0,
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas_trabajadas' => 'decimal:2',
        'estado' => EstadoAsistencia::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /** Quien dio por buena la asistencia. */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    /** Las asistencias de un rango de fechas, con ambos extremos incluidos. */
    public function scopeEntreFechas(Builder $consulta, string $desde, string $hasta): Builder
    {
        return $consulta->whereBetween('fecha', [$desde, $hasta]);
    }
}
