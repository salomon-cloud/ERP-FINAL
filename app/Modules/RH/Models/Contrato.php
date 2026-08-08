<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoContrato;
use App\Modules\RH\Enums\TipoContrato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El historial contractual de un empleado. Un contrato vigente sin fecha_fin es
 * el indefinido; los anteriores se conservan, nunca se borran.
 *
 * `sueldo` es el dato del contrato firmado, para efectos legales e historicos.
 * Lo que la nomina paga hoy es `empleados.sueldo_base` -- son dos cosas
 * distintas a proposito, y ServicioCorridaNomina solo lee la segunda.
 */
class Contrato extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'contratos';

    protected $fillable = [
        'empleado_id',
        'numero_contrato',
        'tipo_contrato',
        'fecha_inicio',
        'fecha_fin',
        'sueldo',
        'jornada_horas',
        'resumen_clausulas',
        'estado',
        'firmado_en',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'tipo_contrato' => 'indefinido',
        'estado' => 'borrador',
        'sueldo' => 0,
        'jornada_horas' => 48,
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'sueldo' => 'decimal:2',
        'jornada_horas' => 'decimal:2',
        'firmado_en' => 'datetime',
        'tipo_contrato' => TipoContrato::class,
        'estado' => EstadoContrato::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoContrato::Vigente);
    }

    /**
     * Contratos vigentes que vencen dentro de los proximos N dias: alimenta el
     * KPI "contratos por vencer" del tablero. Un contrato indefinido
     * (fecha_fin nula) nunca aparece.
     */
    public function scopePorVencer(Builder $consulta, int $dias = 30): Builder
    {
        return $consulta->vigentes()
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [now()->toDateString(), now()->addDays($dias)->toDateString()]);
    }
}
