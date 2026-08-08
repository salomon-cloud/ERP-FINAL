<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una capacidad atomica, codificada <modulo>.<entidad>.<accion>:
 * finanzas.polizas.contabilizar, ventas.pedidos.cancelar,
 * inventario.existencias.ver.
 */
class Privilegio extends Model
{
    use SoftDeletes, TieneCamposAuditoria;

    protected $table = 'privilegios';

    protected $fillable = [
        'codigo',
        'modulo',
        'descripcion',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_privilegios', 'privilegio_id', 'rol_id');
    }
}
