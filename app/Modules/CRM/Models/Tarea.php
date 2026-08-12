<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\CRM\Enums\EstadoTarea;
use App\Modules\CRM\Enums\PrioridadTarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'tareas';

    protected $fillable = [
        'titulo', 'descripcion', 'entidad_tipo', 'entidad_id', 'asignada_a', 'fecha_limite', 'prioridad', 'estado', 'completada_en',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'prioridad' => PrioridadTarea::class,
        'estado' => EstadoTarea::class,
        'completada_en' => 'datetime',
    ];

    public function asignadaA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_a');
    }
}