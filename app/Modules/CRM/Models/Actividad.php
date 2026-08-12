<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\CRM\Enums\TipoActividadCrm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Actividad extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'actividades';

    protected $fillable = [
        'tipo_actividad', 'entidad_tipo', 'entidad_id', 'resumen', 'resultado', 'programada_en', 'completada_en', 'asignada_a',
    ];

    protected $casts = [
        'tipo_actividad' => TipoActividadCrm::class,
        'programada_en' => 'datetime',
        'completada_en' => 'datetime',
    ];

    public function asignadaA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_a');
    }
}