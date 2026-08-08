<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoNomina;
use App\Modules\RH\Observers\ObservadorNomina;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El recibo de un empleado: la tabla `nominas` de SISEN v1, que ahora ademas
 * cuelga de una corrida (corrida_id) y guarda el detalle que la explica
 * (cantidad de horas extra, dias de ausencia).
 *
 * Un recibo puede existir sin corrida (corrida_id nulo): son los que SISEN v1
 * capturaba a mano, y siguen siendo validos.
 */
#[ObservedBy([ObservadorNomina::class])]
class Nomina extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'nominas';

    protected $fillable = [
        'empleado_id',
        'corrida_id',
        'periodo_pago',
        'fecha_pago',
        'sueldo_base',
        'bonos',
        'horas_extra',
        'horas_extra_cantidad',
        'deducciones',
        'dias_ausencia',
        'isr',
        'imss',
        'total_pagar',
        'estado',
        'pagada_en',
        'notas',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'pendiente',
        'bonos' => 0,
        'horas_extra' => 0,
        'horas_extra_cantidad' => 0,
        'deducciones' => 0,
        'dias_ausencia' => 0,
        'isr' => 0,
        'imss' => 0,
        'total_pagar' => 0,
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'sueldo_base' => 'decimal:2',
        'bonos' => 'decimal:2',
        'horas_extra' => 'decimal:2',
        'horas_extra_cantidad' => 'decimal:2',
        'deducciones' => 'decimal:2',
        'dias_ausencia' => 'decimal:2',
        'isr' => 'decimal:2',
        'imss' => 'decimal:2',
        'total_pagar' => 'decimal:2',
        'pagada_en' => 'datetime',
        'estado' => EstadoNomina::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function corrida(): BelongsTo
    {
        return $this->belongsTo(NominaCorrida::class, 'corrida_id');
    }

    /**
     * Percepciones menos deducciones. Es la formula de SISEN v1, y este es el
     * unico lugar del modulo donde vive: el Observer que recalcula el total y
     * ServicioCorridaNomina llaman aqui, nunca la repiten.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function calcularTotal(array $datos): float
    {
        $percepciones = (float) ($datos['sueldo_base'] ?? 0)
            + (float) ($datos['bonos'] ?? 0)
            + (float) ($datos['horas_extra'] ?? 0);

        $deducciones = (float) ($datos['deducciones'] ?? 0)
            + (float) ($datos['isr'] ?? 0)
            + (float) ($datos['imss'] ?? 0);

        return $percepciones - $deducciones;
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        // El grupo es obligatorio: sin el, el orWhereHas se saldria de
        // cualquier filtro previo (->where('estado', ...)->buscar(...)).
        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('periodo_pago', 'like', '%'.$termino.'%')
                ->orWhereHas('empleado', fn (Builder $empleado) => $empleado->buscar($termino));
        });
    }
}
