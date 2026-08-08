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
 * La tabla `departamentos` de SISEN v1, ahora con jerarquia (padre_id), jefe y
 * codigo. Junto con Empleado::jefe forma el organigrama.
 *
 * Convive con App\Models\Departamento (v1), que se queda congelado hasta que
 * las pantallas viejas se porten al modulo: las dos clases leen la misma tabla,
 * pero solo esta lleva auditoria, bitacora y borrado logico.
 */
class Departamento extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'departamentos';

    protected $fillable = [
        'organizacion_id',
        'padre_id',
        'jefe_id',
        'codigo',
        'nombre',
        'descripcion',
        'responsable',
        'estado',
    ];

    /** El mismo valor por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'activo',
    ];

    protected $casts = [
        'estado' => EstadoActivacion::class,
    ];

    /** El departamento del que cuelga este, si no es de primer nivel. */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    /** Quien dirige el departamento. */
    public function jefe(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'jefe_id');
    }

    public function puestos(): HasMany
    {
        return $this->hasMany(Puesto::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoActivacion::Activo);
    }

    /** La busqueda de la barra de filtros: nombre, codigo o responsable. */
    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('nombre', 'like', '%'.$termino.'%')
                ->orWhere('codigo', 'like', '%'.$termino.'%')
                ->orWhere('responsable', 'like', '%'.$termino.'%');
        });
    }
}
