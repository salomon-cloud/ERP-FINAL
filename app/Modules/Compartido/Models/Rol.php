<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes, TieneCamposAuditoria;

    protected $table = 'roles';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'es_sistema',
    ];

    protected $casts = [
        'es_sistema' => 'boolean',
    ];

    public function privilegios(): BelongsToMany
    {
        return $this->belongsToMany(Privilegio::class, 'rol_privilegios', 'rol_id', 'privilegio_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_roles', 'rol_id', 'user_id');
    }
}
