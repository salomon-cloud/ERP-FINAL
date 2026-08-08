<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solo insercion. No existe ninguna ruta, servicio ni comando en toda la
 * aplicacion que modifique o borre una fila de bitacora_auditoria.
 */
class RegistroBitacora extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bitacora_auditoria';

    protected $fillable = [
        'user_id',
        'modulo',
        'accion',
        'entidad_tipo',
        'entidad_id',
        'valores_anteriores',
        'valores_nuevos',
        'direccion_ip',
        'agente_usuario',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Contrato de linea de tiempo compartido con las actividades de CRM. */
    public function aLineaDeTiempo(): array
    {
        return [
            'fecha' => $this->created_at,
            'por' => $this->usuario?->name,
            'tipo' => $this->accion,
            'resumen' => class_basename($this->entidad_tipo).' #'.$this->entidad_id,
        ];
    }
}
