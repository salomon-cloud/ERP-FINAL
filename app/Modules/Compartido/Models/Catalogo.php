<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Listas de valores compartidas entre modulos (condiciones_pago, forma_pago,
 * moneda, ...). Un valor que ademas tiene atributos o relaciones propias merece
 * su tabla de dominio -- ver PLANNING "Catalogs".
 */
class Catalogo extends Model
{
    use SoftDeletes, TieneCamposAuditoria;

    protected $table = 'catalogos';

    protected $fillable = [
        'grupo',
        'codigo',
        'nombre',
        'valor',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    /** Valores activos de un grupo, en orden: Catalogo::grupo('condiciones_pago')->get() */
    public function scopeGrupo(Builder $consulta, string $grupo): Builder
    {
        return $consulta->where('grupo', $grupo)
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre');
    }
}
