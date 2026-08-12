<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contacto extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'contactos';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'prospecto_id', 'nombre', 'apellidos', 'correo', 'telefono', 'puesto', 'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}