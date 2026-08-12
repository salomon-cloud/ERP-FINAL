<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'empresas';

    protected $fillable = [
        'organizacion_id', 'nombre', 'rfc', 'giro', 'sitio_web', 'correo', 'telefono', 'direccion', 'estado',
    ];

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}