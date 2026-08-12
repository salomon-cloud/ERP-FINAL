<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\CRM\Enums\EtapaOportunidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Oportunidad extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'oportunidades';

    protected $fillable = [
        'organizacion_id', 'prospecto_id', 'cliente_id', 'contacto_id', 'nombre', 'descripcion', 'etapa', 'monto',
        'moneda', 'probabilidad', 'fecha_cierre_estimada', 'asignado_a', 'pedido_id', 'ganada_en', 'perdida_en', 'motivo_perdida',
    ];

    protected $casts = [
        'etapa' => EtapaOportunidad::class,
        'monto' => 'decimal:2',
        'probabilidad' => 'decimal:2',
        'fecha_cierre_estimada' => 'date',
        'ganada_en' => 'datetime',
        'perdida_en' => 'datetime',
    ];

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }
}