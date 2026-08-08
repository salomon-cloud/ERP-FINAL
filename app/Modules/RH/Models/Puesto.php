<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoActivacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La tabla `puestos` de SISEN v1, mas codigo, auditoria y borrado logico.
 *
 * El rango sueldo_minimo..sueldo_maximo lo garantiza la base con
 * chk_puestos_rango_sueldo; un sueldo_maximo de 0 significa "sin tope".
 */
class Puesto extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'puestos';

    protected $fillable = [
        'departamento_id',
        'codigo',
        'nombre',
        'descripcion',
        'sueldo_minimo',
        'sueldo_maximo',
        'estado',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'activo',
        'sueldo_minimo' => 0,
        'sueldo_maximo' => 0,
    ];

    protected $casts = [
        'sueldo_minimo' => 'decimal:2',
        'sueldo_maximo' => 'decimal:2',
        'estado' => EstadoActivacion::class,
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoActivacion::Activo);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('nombre', 'like', '%'.$termino.'%')
                ->orWhere('codigo', 'like', '%'.$termino.'%');
        });
    }
}
