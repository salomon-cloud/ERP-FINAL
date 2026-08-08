<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Enums\TipoPermiso;
use App\Modules\RH\Observers\ObservadorPermiso;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La tabla `permisos` de SISEN v1 (solicitudes de permiso, vacaciones e
 * incapacidad), mas dias, goce de sueldo y el rastro de la revision.
 *
 * Ojo con el nombre: en este ERP `permisos` son las solicitudes de RH, no los
 * permisos de autorizacion -- esos se llaman `privilegios` (PLANNING, decision
 * v1.1 numero 3).
 *
 * `con_goce = false` es lo que convierte un permiso en deduccion en la corrida
 * de nomina.
 */
#[ObservedBy([ObservadorPermiso::class])]
class Permiso extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'permisos';

    protected $fillable = [
        'empleado_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'dias',
        'con_goce',
        'motivo',
        'estado',
        'revisado_por',
        'revisado_en',
        'comentario_revision',
    ];

    /**
     * Los mismos valores por omision que declara la migracion.
     *
     * Sin esto, un Permiso recien creado sin `estado` (que es justo lo que hace
     * GuardarPermisoRequest, porque el estado es del Service) tendria estado
     * null en memoria hasta releerlo de la base, y cualquier lectura del enum
     * reventaria.
     */
    protected $attributes = [
        'estado' => 'pendiente',
        'dias' => 0,
        'con_goce' => true,
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'dias' => 'decimal:2',
        'con_goce' => 'boolean',
        'revisado_en' => 'datetime',
        'tipo' => TipoPermiso::class,
        'estado' => EstadoPermiso::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /** Quien aprobo o rechazo la solicitud. */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function scopePendientes(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoPermiso::Pendiente);
    }

    /** Los permisos que se traslapan con un rango: la base de la nomina. */
    public function scopeQueCruzan(Builder $consulta, string $desde, string $hasta): Builder
    {
        return $consulta->where('fecha_inicio', '<=', $hasta)
            ->where('fecha_fin', '>=', $desde);
    }
}
