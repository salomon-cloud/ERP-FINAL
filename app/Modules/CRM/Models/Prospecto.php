<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\CRM\Enums\EstadoProspecto;
use App\Modules\CRM\Enums\OrigenProspecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospecto extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'prospectos';

    protected $fillable = [
        'organizacion_id', 'origen', 'nombre', 'apellidos', 'empresa_nombre', 'correo', 'telefono',
        'estado', 'asignado_a', 'valor_estimado', 'notas', 'cliente_convertido_id', 'convertido_en', 'motivo_perdida',
    ];

    protected $casts = [
        'origen' => OrigenProspecto::class,
        'estado' => EstadoProspecto::class,
        'valor_estimado' => 'decimal:2',
        'convertido_en' => 'datetime',
    ];

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function oportunidades(): HasMany
    {
        return $this->hasMany(Oportunidad::class);
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}